<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_uses_brazilian_portuguese_defaults(): void
    {
        $this->assertSame('Rocha Sports', config('app.name'));
        $this->assertSame('America/Sao_Paulo', config('app.timezone'));
        $this->assertSame('pt_BR', config('app.locale'));
        $this->assertSame('pt_BR', config('app.fallback_locale'));
        $this->assertSame('pt_BR', config('app.faker_locale'));
    }

    public function test_shows_validation_messages_in_portuguese(): void
    {
        $validator = Validator::make(
            ['customer_name' => ''],
            ['customer_name' => ['required']],
        );

        $this->assertSame('O campo nome completo é obrigatório.', $validator->messages()->first('customer_name'));
    }
}
