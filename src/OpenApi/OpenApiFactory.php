<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Example;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\ApiResource\Tool\ToolResource;
use App\OpenApi\Example\CreateToolExample;
use App\OpenApi\Example\ErrorResponseExample;
use App\OpenApi\Example\ToolCollectionExample;
use App\OpenApi\Example\ToolDetailExample;
use App\OpenApi\Example\UpdateToolExample;
use ArrayObject;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final readonly class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        #[AutowireDecorated]
        private OpenApiFactoryInterface $decorated,
        #[Autowire('%api_prefix%')]
        private string $apiPrefix,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();

        foreach ($paths->getPaths() as $path => $pathItem) {
            if (!$pathItem instanceof PathItem) {
                continue;
            }
            $paths->addPath($path, $this->enrichPathItem($path, $pathItem));
        }

        return $openApi;
    }

    private function enrichPathItem(string $path, PathItem $pathItem): PathItem
    {
        $get = $pathItem->getGet();
        if ($get instanceof OpenApiOperation) {
            $pathItem = $pathItem->withGet($this->enrichOperation($path, 'get', $get));
        }

        $post = $pathItem->getPost();
        if ($post instanceof OpenApiOperation) {
            $pathItem = $pathItem->withPost($this->enrichOperation($path, 'post', $post));
        }

        $put = $pathItem->getPut();
        if ($put instanceof OpenApiOperation) {
            $pathItem = $pathItem->withPut($this->enrichOperation($path, 'put', $put));
        }

        return $pathItem;
    }

    private function enrichOperation(string $path, string $method, OpenApiOperation $operation): OpenApiOperation
    {
        $operation = $this->enrichRequestBody($path, $method, $operation);

        $responses = $operation->getResponses() ?? [];

        $successCode = $method === 'post' ? '201' : '200';
        if (isset($responses[$successCode]) && $responses[$successCode] instanceof Response) {
            $responses[$successCode] = $this->enrichSuccessResponse($path, $method, $responses[$successCode]);
        }

        $responses['400'] = $this->validationErrorResponse($path, $method);
        unset($responses['422']);

        if ($this->supportsNotFound($path, $method)) {
            $responses['404'] = $this->notFoundResponse($path);
        }

        $responses['500'] = $this->internalErrorResponse();

        return $operation->withResponses($responses);
    }

    private function enrichRequestBody(string $path, string $method, OpenApiOperation $operation): OpenApiOperation
    {
        $example = $this->requestBodyExample($path, $method);
        if ($example === null) {
            return $operation;
        }

        $requestBody = $operation->getRequestBody();
        if (!$requestBody instanceof RequestBody) {
            return $operation;
        }

        return $operation->withRequestBody($requestBody->withContent(new ArrayObject([
            'application/json' => new MediaType(example: $example),
        ])));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestBodyExample(string $path, string $method): ?array
    {
        if ($method === 'post' && $this->isCollectionPath($path)) {
            return CreateToolExample::INPUT;
        }

        if ($method === 'put' && $this->isItemPath($path)) {
            return UpdateToolExample::INPUT;
        }

        return null;
    }

    private function enrichSuccessResponse(string $path, string $method, Response $response): Response
    {
        $examples = $this->successExamples($path, $method);
        if ($examples === null) {
            return $response;
        }

        return $response->withContent(new ArrayObject([
            'application/json' => new MediaType(examples: new ArrayObject($examples)),
        ]));
    }

    /**
     * @return array<string, \ApiPlatform\OpenApi\Model\Example>|null
     */
    private function successExamples(string $path, string $method): ?array
    {
        if ($method === 'get' && $this->isCollectionPath($path)) {
            return [
                'no_filters' => $this->example(
                    'GET /api/tools — liste complète, sans filtre ni pagination',
                    ToolCollectionExample::NO_FILTERS,
                ),
                'with_filters' => $this->example(
                    'GET /api/tools?department=Engineering&status=active',
                    ToolCollectionExample::WITH_FILTERS,
                ),
                'with_pagination_and_sort' => $this->example(
                    'GET /api/tools?department=Engineering&sort_by=cost&order=desc&page=1&limit=5',
                    ToolCollectionExample::WITH_PAGINATION_AND_SORT,
                ),
                'empty_db' => $this->example(
                    'GET /api/tools — DB vide',
                    ToolCollectionExample::EMPTY_DB,
                ),
                'no_match' => $this->example(
                    'GET /api/tools?min_cost=99999 — filtres sans résultat',
                    ToolCollectionExample::NO_MATCH,
                ),
                'page_out_of_range' => $this->example(
                    'GET /api/tools?page=100&limit=10 — page inexistante',
                    ToolCollectionExample::PAGE_OUT_OF_RANGE,
                ),
            ];
        }

        if ($method === 'get' && $this->isItemPath($path)) {
            return [
                'found' => $this->example(
                    'GET /api/tools/5 — détail complet',
                    ToolDetailExample::FOUND,
                ),
                'low_usage' => $this->example(
                    'GET /api/tools/12 — outil peu utilisé (0 session sur 30j)',
                    ToolDetailExample::LOW_USAGE,
                ),
            ];
        }

        if ($method === 'post' && $this->isCollectionPath($path)) {
            return [
                'created' => $this->example(
                    'POST /api/tools — outil créé (201)',
                    CreateToolExample::CREATED,
                ),
            ];
        }

        if ($method === 'put' && $this->isItemPath($path)) {
            return [
                'updated' => $this->example(
                    'PUT /api/tools/5 — outil mis à jour (200)',
                    UpdateToolExample::UPDATED,
                ),
            ];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $value
     */
    private function example(string $summary, array $value): Example
    {
        return new Example(
            summary: $summary,
            value: $value,
        );
    }

    private function validationErrorResponse(string $path, string $method): Response
    {
        if ($method === 'post' && $this->isCollectionPath($path)) {
            return $this->bodyValidationResponse([
                'field_errors' => $this->example(
                    'Corps avec plusieurs violations de champ',
                    CreateToolExample::VALIDATION_ERRORS,
                ),
                'unknown_fields' => $this->example(
                    'Corps avec champs inconnus (strict JSON)',
                    CreateToolExample::UNKNOWN_FIELDS,
                ),
            ]);
        }

        if ($method === 'put' && $this->isItemPath($path)) {
            return $this->bodyValidationResponse([
                'id_not_integer' => $this->example(
                    'ID de path invalide',
                    ErrorResponseExample::ID_NOT_INTEGER,
                ),
                'field_errors' => $this->example(
                    'Corps avec plusieurs violations de champ',
                    UpdateToolExample::VALIDATION_ERRORS,
                ),
                'unknown_fields' => $this->example(
                    'Corps avec champs inconnus (strict JSON)',
                    CreateToolExample::UNKNOWN_FIELDS,
                ),
            ]);
        }

        $example = $this->isItemPath($path)
            ? ErrorResponseExample::ID_NOT_INTEGER
            : ErrorResponseExample::VALIDATION_FAILED;

        return new Response(
            description: 'Validation failed — one or several query parameters, path variables or body fields are invalid',
            content: new ArrayObject([
                'application/json' => new MediaType(example: $example),
            ]),
        );
    }

    /**
     * @param array<string, Example> $examples
     */
    private function bodyValidationResponse(array $examples): Response
    {
        return new Response(
            description: 'Validation failed — body rejected (field constraints, unknown fields in strict JSON mode, or bad path variable)',
            content: new ArrayObject([
                'application/json' => new MediaType(
                    examples: new ArrayObject($examples),
                ),
            ]),
        );
    }

    private function notFoundResponse(string $path): Response
    {
        $example = $this->isItemPath($path)
            ? ErrorResponseExample::TOOL_NOT_FOUND
            : ErrorResponseExample::RESOURCE_NOT_FOUND;

        return new Response(
            description: 'Resource not found',
            content: new ArrayObject([
                'application/json' => new MediaType(example: $example),
            ]),
        );
    }

    private function internalErrorResponse(): Response
    {
        return new Response(
            description: 'Internal server error (for example, the database is unreachable)',
            content: new ArrayObject([
                'application/json' => new MediaType(
                    example: ErrorResponseExample::DATABASE_UNAVAILABLE,
                ),
            ]),
        );
    }

    private function supportsNotFound(string $path, string $method): bool
    {
        return $this->isItemPath($path) || in_array($method, ['put', 'patch', 'delete'], true);
    }

    private function isCollectionPath(string $path): bool
    {
        return rtrim($path, '/') === $this->apiPrefix . ToolResource::URI_BASE;
    }

    private function isItemPath(string $path): bool
    {
        return str_contains($path, ToolResource::URI_BASE . '/{');
    }
}
