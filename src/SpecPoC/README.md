# swagger-php spec pipeline PoC

This branch is a proof of concept to illustrate what NelmioApiDocBundle built on swagger-php's **[spec pipeline](https://zircote.github.io/swagger-php/guide/modes.html#spec)** (`OpenApi\Spec`) could look like.
The spec pipeline code was added in 6.5.0 and is still considered BETA, though it tracks classic closely: swagger-php's
scratch fixtures run all three modes against one shared expected document, and few need a spec-specific override.

Not complete and rough — just a showcase of what is possible. Downstream support was one key focus writing the new code —
hope it is useful. The hooks it uses are documented in [Extension points](https://zircote.github.io/swagger-php/guide/extension-points.html), with [what ships by default](https://zircote.github.io/swagger-php/reference/extension-points.html).

## Running it

```
php poc.php            # every controller
php poc.php mapquery   # just the ones whose name matches
```

Needs `zircote/swagger-php` 6.8 or later. It prints one YAML document per controller to stdout.
`php poc.php mapquery` prints:

```yaml
openapi: 3.1.0
info:
  title: 'NelmioApiDocBundle spec PoC'
  version: '0.1'
paths:
  /article_map_query_parameter:
    get:
      operationId: b49b6ff950d7d40f9ffafac2a8d5cc1b
      parameters:
        -
          name: someInt
          in: query
          required: true
          schema:
            type: integer
        -
          name: someFloat
          in: query
          required: true
          schema:
            type: number
            format: float
```

## The integration, in one method

`Run::build()` is the whole integration:

```php
$result = (new Builder())
    ->setMode(Builder\Mode::SPEC)
    ->setLogger(new ConsoleLogger(new ConsoleOutput()))
    ->addSource([
        new ReflectionClass(Doc::class),
        new ReflectionClass($className),
    ])
    ->withAugmenters(fn(Pipeline $augmenters) => $augmenters
        ->insert(new ModelAugmenter($modelAssembler), Augmenter\Inheritance::class)
        ->insert(new MapRequestPayloadAugmenter(), Augmenter\Names::class)
    )
    ->withAttributeFactory(fn(AttributeFactory $attributeFactory) => $attributeFactory
        ->withTranslators(fn(TypedList $translators) => $translators
            ->add(new ClassRouteTranslator())
            ->add(new MethodRouteTranslator())
            ->add(new MapQueryParameterTranslator())
            ->add(new MapRequestPayloadTranslator())
        )
    )
    ->build();

echo $result->toYaml();
```

`Doc` exists only to carry an `#[OA\Info]`, so the compiler considers the document valid. `$modelAssembler` is a second,
separate `Assembler` used by `ModelAugmenter` to pull model classes in on demand — see below.

This is not a smaller pile of parts than the bundle has today: the attributes below subclass spec types, `withAugmenters()` and `withTranslators()` are hooks into the pipeline, and augmenters are what
processors were. What differs is the kind of contact. Nothing reaches into internals — every hook used here is a documented
extension point — and the attributes are plain DTOs taking constructor arguments. In particular there is no `Context` to
build, copy or thread downwards: `_context` and `Util::createContext()` run through most of the bundle's describers today,
and the PoC needs neither.

## What stood in for what

Only the pieces this PoC used.

| Bundle today | In this PoC |
|---|---|
| `ApiDocGenerator` / `OpenApiGenerator` | [`Builder`](https://zircote.github.io/swagger-php/reference/builder.html), in `Run::build()` — every mode, not only spec |
| `RouteDescriber`, for class and method `#[Route]` | `ClassRouteTranslator`, `MethodRouteTranslator` |
| `SymfonyMapQueryParameterDescriber` | `MapQueryParameterTranslator` |
| `SymfonyMapRequestPayloadDescriber`, `Processor\MapRequestPayloadProcessor` | `MapRequestPayloadTranslator` and `MapRequestPayloadAugmenter`, both stubs |
| `ObjectModelDescriber`, and the `Model` attribute | `SchemaAutoTranslator` on a second `Assembler`, driven by `ModelAugmenter`; `ContentModel` / `RefModel` |

Untouched, and between them the bulk of the real work: everything else under `Describer/`, `ModelDescriber/`,
`PropertyDescriber/` and `TypeDescriber/`, the remaining `RouteArgumentDescriber`s, and `ModelRegistry`. The hooks they
would use exist — a whole-document `DescriberInterface` corresponds to an augmenter, a `PropertyDescriber` mostly to the
pipeline's own type resolution — but nothing here exercises them. `ModelRegistry` has no counterpart at all, which is
[Generics](#generics).

One difference runs through the table: a describer receives the document and mutates it, while a translator receives a
reflector and *returns* attributes for the pipeline to place.

## What's in it, in detail

### This folder — the PoC

**[Translators](https://zircote.github.io/swagger-php/guide/extension-points.html#translators)** run while scanning, once per reflector, and turn a Symfony
attribute into spec attributes. This is where the bundle's "understand the framework" logic lives.

| Translator | What it does |
|---|---|
| `ClassRouteTranslator` | class-level `#[Route]` → `OA\PathItem(prefix:)`, or an `OA\Operation` when the class is invokable |
| `MethodRouteTranslator` | method-level `#[Route]` → `OA\Operation` |
| `MapQueryParameterTranslator` | `#[MapQueryParameter]` → `OA\Parameter\Query`, name taken from the parameter |
| `MapRequestPayloadTranslator` | `#[MapRequestPayload]` → an `OperationAttachable`, deferred rather than resolved |
| `SchemaAutoTranslator` | used only by the model assembler: auto-`OA\Schema` on a class, auto-`OA\Property` on public properties and constructor parameters |

**[Augmenters](https://zircote.github.io/swagger-php/guide/extension-points.html#augmenters)** run over the assembled document.

| Augmenter | What it does |
|---|---|
| `ModelAugmenter` | resolves `RefModel` refs to the class name, collects the referenced class through its own `Assembler`, and follows nested types so a model graph comes in whole |
| `MapRequestPayloadAugmenter` | finds the deferred attachables and attaches a request body to the operation |

**Attributes** are three small [subclasses of spec types](https://zircote.github.io/swagger-php/guide/extension-points.html#subclassing-a-spec-attribute).

| Attribute | Why it exists |
|---|---|
| `RefModel extends OA\Schema\Ref` | a marker, so `ModelAugmenter` can tell a model ref from any other ref |
| `ContentModel extends OA\MediaType\Json` | sugar — `new ContentModel(Foo::class)` instead of spelling out the media type and ref |
| `OperationAttachable extends OA\Attachable` | carries a `MapRequestPayload` up to the `OA\Operation` it belongs to |

`ModelAugmenter` collects the class itself because the bundle's models carry no spec attributes, and the default
resolver declines a class that yields no component. The second `Assembler` is there for that, and it is the roughest
edge in here — see [Generics](#generics).

`MapRequestPayload` is split across a translator and an augmenter. The method might also carry a real
`#[OA\RequestBody]`, which the translator cannot see from a single parameter reflector, so the translator wraps and the
augmenter decides.

The bundle splits it the same way. `SymfonyMapRequestPayloadDescriber` reads the attribute and registers the model, and
`Processor\MapRequestPayloadProcessor` builds the request body once the document is complete — "A processor is used to
ensure that a Model has been created", as its docblock puts it. The difference is the handover: the describer stores the
`ArgumentMetadata` and the model ref on `$operation->_context` under two class-prefixed constants for the processor to
read back, where the translator here returns an `OperationAttachable` that the operation carries in its `attachables`
slot.

The augmenter is a stub — it creates the `OA\RequestBody` and stops at a literal `// do stuff with mapRequestPayload`.
`Run`'s docblock sketches two alternatives that lean on the captured reflectors instead, both probably better.

### Changes to the test controllers

Five files under `tests/Functional/Controller/` are edited **in place**: four of the controllers the PoC builds
documents for, and the `AbstractDocumentController` that `InvoiceDocumentController` extends.

- `use OpenApi\Attributes as OA` → `use OpenApi\Spec as OA` (all five)
- `OA\QueryParameter` → `OA\Parameter\Query`, `OA\JsonContent` → `OA\MediaType\Json` (spec
  spells these differently — the [Spec Attributes reference](https://zircote.github.io/swagger-php/reference/spec-attributes.html) lists every one)
- `Model` → `ContentModel` / `RefModel`, the bundle's own `Model` attribute having no spec
  equivalent

All three are what a migration would have to face. The first two are mechanical, and once classic is removed the spec
attributes will most likely take over `OpenApi\Attributes` and undo the first one. The `Model` swap is not mechanical:
there is nothing to rename it to, so it means deciding what replaces it.

The remaining controller, `InvokableController`, was left on `OpenApi\Attributes`, and a half-converted file loses attributes
quietly: its `#[OA\Response(response: 200)]` is absent from the document and nothing reports it. The path and operation
still come out, so the loss is easy to miss.

Editing them in place has a cost: those five controllers belong to the bundle's own functional suite, so
this branch can no longer run it — which is the most direct way to check that the PoC produces the document the bundle already
produces. Spec-specific copies under this folder would have kept both pipelines runnable side by side.

## What it shows

- Translators, augmenters and attachables covered every case here without touching swagger-php
  or reaching into pipeline internals.
- **Attributes are plain DTOs.** Nothing has to build, copy or propagate a `Context`, which is a good deal of what the
  bundle's describers carry around today.
- **Sources are reflectors, and referenced classes resolve themselves.** `Run::build()` hands the `Builder`
  `ReflectionClass` instances, not a directory, and a `$ref` to a class that was never handed over is collected by the
  [resolver step](https://zircote.github.io/swagger-php/guide/extension-points.html#resolvers). The source list only has
  to name the controllers, which a bundle already has from the router.
- Routes, query parameters and model refs work end to end.

## What it does not show

- No tests, DI, config, caching, or areas.
- Request body mapping stops at creating the object.
- Five controllers, chosen for the attributes they cover rather than for being representative.
- Model handling is a stand-in. The second `Assembler` and `ModelAugmenter` do by hand what `ModelRegistry` does in the
  bundle.
- Generic instantiations do not each get their own schema.

## Generics

`GenericTypesController` is where the gap is widest. The bundle emits eleven schemas, one per
instantiation — `GenericClass<string>` as `GenericClass`, `<int>` as `GenericClass2`,
`<GenericClass<int>>` as `GenericClass3`. This PoC emits four, because swagger-php maps `Foo<T>`
to `Foo` and OpenAPI has no way to say more. Reifying an instantiation as its own component is a
naming layer, which is where the bundle's `ModelRegistry` already lives; nothing here attempts
it.
The solution here would be to replace the default [`Reflection` resolver](https://zircote.github.io/swagger-php/guide/extension-points.html#resolvers) with a custom
one that uses the registry.
