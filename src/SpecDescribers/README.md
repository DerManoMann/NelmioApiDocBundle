# Spec describers - proof of concept

An experiment, not a contribution. It runs NelmioApiDocBundle's own describers on top of
swagger-php's spec pipeline (`OpenApi\Builder` in `Mode::SPEC`), using only swagger-php's
extension points, to find out what swagger-php is missing for an integration like this.

Kept rough on purpose. Config is hardcoded, and nelmio's DI wiring is out of scope.

## What is in here

- `SpecGenerator` - the replacement for `ApiDocGenerator`, and the place where everything is wired
  into the `Builder`.
- `NelmioServices` - nelmio's test kernel used as a service locator, so the describers come
  configured as in nelmio's own test run.
- `RouteOperationTranslator`, `SecurityTranslator`, `NelmioOperationTranslator` - attribute
  translators. Operations are created from the route table during assembly, so attributes on a
  route method nest into them.
- `DescriberContribution`, `ModelContribution` - `withSpecification()` hooks. nelmio's describers
  and `ModelRegistry` run into one scratch classic document, which `DocumentBridge` converts.
- `*Augmenter` - nelmio's processors and defaults as spec augmenters.
- `DeepOperations` - `Merge\Operations` with a deep fold of `requestBody`.
- `Model` - nelmio's `#[Model]` as a `Spec\Schema\Ref`, so it type-checks inside spec attributes.

The fixture controllers in `tests/Functional/Controller/` were migrated to `OpenApi\Spec` with
swagger-php's rector set (`rector/set/classic-to-spec.php`) and then use this `Model`. Entities
are left classic, so nelmio's model describers still read them. `ApiController.php` has two
hand edits, at the comments mentioning nelmio and the findings.

## Setup

`composer.json` points `zircote/swagger-php` at a checkout of swagger-php master next to this
one, via a path repository (`../swagger-php+nelmio-describers`, versioned as `6.99.0`).

```shell
git -C ../swagger-php worktree add --detach ../swagger-php+nelmio-describers upstream/master
composer install --no-scripts
```

## Running

Compare one `ControllerTest` case with nelmio's committed fixture (3.0 only):

```shell
php src/SpecDescribers/run.php MapRequestPayloadController
php src/SpecDescribers/run.php InvokableController insecured
```

Expected and actual output are written to `POC_OUT` (default: the system temp dir).

The committed fixtures predate swagger-php #2251, so `run.php` folds that one shape back and says
so ("drift folded"). The better comparison is an oracle: nelmio's own classic generator on the
same swagger-php master, in a clean checkout.

```shell
git worktree add --detach ../NelmioApiDocBundle+oracle upstream/5.x
cp -a vendor ../NelmioApiDocBundle+oracle/
(cd ../NelmioApiDocBundle+oracle && mkdir -p /tmp/oracle \
    && php ../NelmioApiDocBundle+spec-describers/src/SpecDescribers/tools/oracle.php 3.0.0 /tmp/oracle \
    && php ../NelmioApiDocBundle+spec-describers/src/SpecDescribers/tools/oracle.php 3.1.0 /tmp/oracle \
    && php ../NelmioApiDocBundle+spec-describers/src/SpecDescribers/tools/oracle-kernel.php /tmp/oracle)

src/SpecDescribers/tools/runall.sh 3.0.0 /tmp/oracle
src/SpecDescribers/tools/runall.sh 3.1.0 /tmp/oracle
POC_ORACLE=/tmp/oracle php src/SpecDescribers/run-kernel.php
python3 src/SpecDescribers/tools/summ.py /tmp/TestKernel.expected.json /tmp/TestKernel.actual.json
```

`run-kernel.php` covers the full `TestKernel` (`ApiController`, `routes.yaml`, API Platform),
default area. `POC_VERSION` picks the OpenAPI version for both runners.

`spike.php` is the phase 1 spike for the classic to spec conversion, and `tools/reuse.php` builds
twice with one `Builder`.
