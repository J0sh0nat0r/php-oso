<?php

namespace J0sh0nat0r\Oso;

use InvalidArgumentException;
use J0sh0nat0r\Oso\Exceptions\InlineQueryFailedException;
use J0sh0nat0r\Oso\FFI\Ffi;
use J0sh0nat0r\Oso\FFI\Polar as FFIPolar;

/**
 * @template TQuery
 * @template TResource
 */
class Polar
{
    protected FFIPolar $ffiPolar;

    /**
     * @var Host<TQuery, TResource>
     */
    public Host $host;

    public function __construct()
    {
        $this->ffiPolar = Ffi::get()->polarNew();
        $this->host = new Host($this->ffiPolar);

        // Register global constants.
        $this->registerConstant(null, 'nil');

        // Register built-in classes.
        $this->registerClass('boolean', 'Boolean');
        $this->registerClass('integer', 'Integer');
        $this->registerClass('float', 'Float');
        $this->registerClass('array', 'List');
        $this->registerClass('array', 'Dictionary');
        $this->registerClass('string', 'String');
    }

    public function clearRules(): void
    {
        $this->ffiPolar->clearRules();
    }

    public function loadFiles(array $filenames): void
    {
        if (empty($filenames)) {
            return;
        }

        $sources = [];

        foreach ($filenames as $filename) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if ($ext !== 'polar') {
                throw new InvalidArgumentException("PolarFileExtensionError($filename)");
            }

            $contents = file_get_contents($filename);

            if ($contents === false) {
                throw new InvalidArgumentException("PolarFileNotFoundError($filename)");
            }

            $sources[] = new Source($contents, $filename);
        }

        $this->loadSources($sources);
    }

    public function loadStr(string $str, ?string $filename = null): void
    {
        $this->loadSources([new Source($str, $filename)]);
    }

    public function query(Predicate|string $query, array $bindings = [], bool $acceptExpression = false): Query
    {
        $newHost = clone $this->host;
        $newHost->setAcceptExpression($acceptExpression);

        $ffiQuery = is_string($query)
            ? $this->ffiPolar->newQueryFromStr($query)
            : $this->ffiPolar->newQueryFromTerm($newHost->toPolarTerm($query));

        return new Query($ffiQuery, $newHost, $bindings);
    }

    public function queryRule(string $rule, array $bindings = [], bool $acceptExpression = false, ...$args): Query
    {
        return $this->query(new Predicate($rule, ...$args), $bindings, $acceptExpression);
    }

    public function queryRuleOnce(string $rule, ...$args): bool
    {
        return $this->queryRule($rule, [], false, ...$args)->getIterator()->valid();
    }

    /**
     * @param class-string|string $class
     */
    public function registerClass(string $class, ?string $name = null, array $fields = []): void
    {
        $classType = ClassType::fromName($class);

        $name = $this->host->cacheClass($classType, $name, $fields);

        $this->registerConstant($classType, $name);

        $this->host->registerMros();
    }

    public function registerConstant($value, string $name): void
    {
        $this->ffiPolar->registerConstant($name, $this->host->toPolarTerm($value));
    }

    /**
     * @param array<Source> $sources
     */
    private function loadSources(array $sources): void
    {
        $this->host->registerMros();
        $this->ffiPolar->load($sources);
        $this->checkInlineQueries();
    }

    private function checkInlineQueries(): void
    {
        $ffiQuery = $this->ffiPolar->nextInlineQuery();

        while ($ffiQuery !== null) {
            $query = new Query($ffiQuery, $this->host, []);

            if (!$query->getIterator()->valid()) {
                throw new InlineQueryFailedException($ffiQuery->source());
            }

            $ffiQuery = $this->ffiPolar->nextInlineQuery();
        }
    }
}
