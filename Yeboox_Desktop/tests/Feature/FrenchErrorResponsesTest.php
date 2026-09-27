<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FrenchErrorResponsesTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_messages_are_in_french(): void
    {
        $validator = Validator::make([], ['email' => ['required', 'email']]);

        $this->assertSame('Le champ adresse e-mail est obligatoire.', $validator->errors()->first('email'));
    }

    public function test_html_not_found_page_is_in_french(): void
    {
        $this->get('/page-qui-n-existe-pas')
            ->assertNotFound()
            ->assertSeeText('Page introuvable')
            ->assertSeeText('La page ou la ressource demandée n’existe pas ou a été déplacée.');
    }

    public function test_api_not_found_response_is_in_french(): void
    {
        $this->getJson('/api/ressource-inconnue')
            ->assertNotFound()
            ->assertExactJson(['message' => 'La route ou la ressource demandée est introuvable.']);
    }
}
