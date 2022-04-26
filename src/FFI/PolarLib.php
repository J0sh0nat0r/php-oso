<?php

namespace J0sh0nat0r\Oso\FFI;

use FFI;
use J0sh0nat0r\Oso\Exceptions\InternalErrorException;
use JsonException;
use RuntimeException;

class PolarLib
{
    protected static PolarLib $instance;

    protected FFI|PolarExtern $ffi;

    protected function __construct()
    {
        $fileName = match (PHP_OS_FAMILY) {
            'Windows' => 'polar.dll',
            'Darwin'  => 'libpolar.dylib',
            'Linux'   => 'libpolar.so',
            default   => throw new RuntimeException('Unsupported OS: '.PHP_OS_FAMILY),
        };

        $this->ffi = FFI::cdef(
            file_get_contents(__DIR__.'/../../lib/polar.h'),
            __DIR__."/../../lib/$fileName"
        );
    }

    public static function deserialize(string $value)
    {
        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InternalErrorException("Failed to deserialize data from FFI: $value");
        }
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public static function serialize($value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InternalErrorException('Failed to serialize data for FFI');
        }
    }

    public function polarNew(): Polar
    {
        return new Polar($this, $this->ffi->polar_new());
    }

    public function polarLoad(Polar $polar, string $sources): CResultVoid
    {
        return new CResultVoid($this, $this->ffi->polar_load($polar->get(), $sources));
    }

    public function polarClearRules(Polar $polar): CResultVoid
    {
        return new CResultVoid($this, $this->ffi->polar_clear_rules($polar->get()));
    }

    public function polarRegisterConstant(Polar $polar, string $name, string $value): CResultVoid
    {
        return new CResultVoid($this, $this->ffi->polar_register_constant($polar->get(), $name, $value));
    }

    public function polarRegisterMro(Polar $polar, string $name, string $mro): CResultVoid
    {
        return new CResultVoid($this, $this->ffi->polar_register_mro($polar->get(), $name, $mro));
    }

    public function polarNextInlineQuery(Polar $polar, int $trace): ?Query
    {
        $ptr = $this->ffi->polar_next_inline_query($polar->get(), $trace);

        return $ptr === null || FFI::isNull($ptr) ? null : new Query($this, $ptr);
    }

    /**
     * @return CResultPointer<Query>
     */
    public function polarNewQueryFromTerm(Polar $polar, string $queryTerm, int $trace): CResultPointer
    {
        return new CResultPointer(
            $this,
            $this->ffi->polar_new_query_from_term($polar->get(), $queryTerm, $trace),
            Query::class
        );
    }

    /**
     * @return CResultPointer<Query>
     */
    public function polarNewQuery(Polar $polar, string $queryStr, int $trace): CResultPointer
    {
        return new CResultPointer(
            $this,
            $this->ffi->polar_new_query($polar->get(), $queryStr, $trace),
            Query::class
        );
    }

    /**
     * @return CResultPointer<RustString>
     */
    public function polarNextPolarMessage(Polar $polar): CResultPointer
    {
        return new CResultPointer(
            $this,
            $this->ffi->polar_next_polar_message($polar->get()),
            RustString::class
        );
    }

    /**
     * @return CResultPointer<QueryEvent>
     */
    public function polarNextQueryEvent(Query $query): CResultPointer
    {
        return new CResultPointer(
            $this,
            $this->ffi->polar_next_query_event($query->get()),
            QueryEvent::class
        );
    }

    public function polarDebugCommand(Query $query, string $value): CResultVoid
    {
        return new CResultVoid($this, $this->ffi->polar_debug_command($query->get(), $value));
    }

    public function polarCallResult(Query $query, int $callId, string $term): CResultVoid
    {
        return new CResultVoid($this, $this->ffi->polar_call_result($query->get(), $callId, $term));
    }

    public function polarQuestionResult(Query $query, int $callId, int $result): CResultVoid
    {
        return new CResultVoid($this, $this->ffi->polar_question_result($query->get(), $callId, $result));
    }

    public function polarApplicationError(Query $query, string $message): CResultVoid
    {
        return new CResultVoid($this, $this->ffi->polar_application_error($query->get(), $message));
    }

    /**
     * @return CResultPointer<RustString>
     */
    public function polarNextQueryMessage(Query $query): CResultPointer
    {
        return new CResultPointer(
            $this,
            $this->ffi->polar_next_query_message($query->get()),
            RustString::class
        );
    }

    /**
     * @return CResultPointer<RustString>
     */
    public function polarQuerySourceInfo(Query $query): CResultPointer
    {
        return new CResultPointer(
            $this,
            $this->ffi->polar_query_source_info($query->get()),
            RustString::class
        );
    }

    public function polarBind(Query $query, string $name, string $value): CResultVoid
    {
        return new CResultVoid($this, $this->ffi->polar_bind($query->get(), $name, $value));
    }

    public function polarGetExternalId(Polar $polar): int
    {
        return $this->ffi->polar_get_external_id($polar->get());
    }

    public function stringFree(RustString $s): int
    {
        return $this->ffi->string_free($s->get());
    }

    public function polarFree(Polar $polar): int
    {
        return $this->ffi->polar_free($polar->get());
    }

    public function queryFree(Query $query): int
    {
        return $this->ffi->query_free($query->get());
    }

    public function resultFree(CResult $result): int
    {
        return $this->ffi->result_free(FFI::cast($this->ffi->type('polar_CResult_c_void*'), $result->get()));
    }

    /**
     * @return CResultPointer<RustString>
     */
    public function polarBuildDataFilter(Polar $polar, string $types, string $results, string $variable, string $classTag): CResultPointer
    {
        return new CResultPointer(
            $this,
            $this->ffi->polar_build_data_filter($polar->get(), $types, $results, $variable, $classTag),
            RustString::class
        );
    }

    /**
     * @return CResultPointer<RustString>
     */
    public function polarBuildFilterPlan(Polar $polar, string $types, string $results, string $variable, string $classTag): CResultPointer
    {
        return new CResultPointer(
            $this,
            $this->ffi->polar_build_filter_plan($polar->get(), $types, $results, $variable, $classTag),
            RustString::class
        );
    }
}
