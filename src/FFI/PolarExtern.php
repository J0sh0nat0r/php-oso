<?php

namespace J0sh0nat0r\Oso\FFI;

use FFI\CData;

interface PolarExtern
{
    public function polar_new(): CData;

    public function polar_load(CData $polar_ptr, string $sources): CData;

    public function polar_clear_rules(CData $polar_ptr): CData;

    public function polar_register_constant(CData $polar_ptr, string $name, string $value): CData;

    public function polar_register_mro(CData $polar_ptr, string $name, string $mro): CData;

    public function polar_next_inline_query(CData $polar_ptr, int $trace): ?CData;

    public function polar_new_query_from_term(CData $polar_ptr, string $query_term, int $trace): CData;

    public function polar_new_query(CData $polar_ptr, string $query_str, int $trace): CData;

    public function polar_next_polar_message(CData $polar_ptr): CData;

    public function polar_next_query_event(CData $query_ptr): CData;

    public function polar_debug_command(CData $query_ptr, string $value): CData;

    public function polar_call_result(CData $query_ptr, int $call_id, string $term): CData;

    public function polar_question_result(CData $query_ptr, int $call_id, int $result): CData;

    public function polar_application_error(CData $query_ptr, string $message): CData;

    public function polar_next_query_message(CData $query_ptr): CData;

    public function polar_query_source_info(CData $query_ptr): CData;

    public function polar_bind(CData $query_ptr, string $name, string $value): CData;

    public function polar_get_external_id(CData $polar_ptr): int;

    public function string_free(CData $s): int;

    public function polar_free(CData $polar): int;

    public function query_free(CData $result): int;

    public function result_free(CData $result): int;

    public function polar_build_data_filter(CData $polar_ptr, string $types, string $results, string $variable, string $class_tag): CData;

    public function polar_build_filter_plan(CData $polar_ptr, string $types, string $results, string $variable, string $class_tag): CData;
}