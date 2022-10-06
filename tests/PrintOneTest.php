<?php

use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Nexxtbi\PrintOne\DTO\Template;
use Nexxtbi\PrintOne\PrintOne;
use Nexxtbi\PrintOne\Tests\TestCase;

class PrintOneTest extends TestCase
{
    public function test_it_can_be_initiated_with_config(): void
    {
        $printOne = new PrintOne(key: "foo");

        $this->assertInstanceOf(PrintOne::class, $printOne);
    }

    public function test_it_can_retreive_templates(): void
    {
        $fakeResponse = [
            "data" => [
                [
                    "id" => "tmpl_a8763477-2430-4034-880b-668604e61abb",
                    "name" => "voorkant",
                    "format" => "POSTCARD_A6",
                    "version" => 6,
                    "updatedAt" => "2022-09-27T14:48:00.514Z"
                ],
                [
                    "id" => "tmpl_bf02958a-6495-4432-bc10-4c64d30c635b",
                    "name" => "nieuwe_woning_achterkant",
                    "format" => "POSTCARD_A6",
                    "version" => 26,
                    "updatedAt" => "2022-08-19T08:25:27.399Z"
                ],
                [
                    "id" => "tmpl_901974e2-d535-444e-9ec1-c729c2c62146",
                    "name" => "nieuwe_woning_voorkant",
                    "format" => "POSTCARD_A6",
                    "version" => 3,
                    "updatedAt" => "2022-08-18T14:39:13.629Z"
                ],
                [
                    "id" => "tmpl_3c137cc0-e722-4a38-89b0-a824114e0a7e",
                    "name" => "achterkant",
                    "format" => "POSTCARD_A6",
                    "version" => 3,
                    "updatedAt" => "2022-07-12T09:40:26.504Z"
                ]
            ],
            "total" => 4,
            "page" => 1,
            "pageSize" => 10,
            "pages" => 1,
            "filters" => []
        ];

        Http::fake([
            'https://api.print.one/v1/templates?*' => Http::response($fakeResponse),
        ]);

        $printOne = new PrintOne(key: "foo");

        $templates = $printOne->templates(page: 1, size: 50);

        $this->assertInstanceOf(Collection::class, $templates);
        $this->assertContainsOnlyInstancesOf(Template::class, $templates);

        $this->assertEquals($fakeResponse["data"][0]['id'], $templates[0]->id);
        $this->assertEquals($fakeResponse["data"][0]['name'], $templates[0]->name);
        $this->assertEquals($fakeResponse["data"][0]['format'], $templates[0]->format);
        $this->assertEquals($fakeResponse["data"][0]['version'], $templates[0]->version);
        $this->assertTrue(Carbon::parse($fakeResponse["data"][0]['updatedAt'], 'UTC')->eq($templates[0]->updatedAt));

        Http::assertSent(function(Request $request){
            return $request->hasHeader('X-Api-Key', 'foo') && $request->url() == 'https://api.print.one/v1/templates?page=1&size=50';
        });
    }
}
