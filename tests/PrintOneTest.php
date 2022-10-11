<?php

namespace Nexibi\PrintOne\Tests;

use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Nexibi\PrintOne\DTO\Address;
use Nexibi\PrintOne\DTO\Order;
use Nexibi\PrintOne\DTO\Postcard;
use Nexibi\PrintOne\DTO\Template;
use Nexibi\PrintOne\Enums\Format;
use Nexibi\PrintOne\Exceptions\CouldNotFetchPreview;
use Nexibi\PrintOne\Exceptions\CouldNotFetchTemplates;
use Nexibi\PrintOne\Exceptions\CouldNotPlaceOrder;
use Nexibi\PrintOne\Facades\PrintOne;
use Nexibi\PrintOne\Tests\TestCase;

class PrintOneTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::set('print-one.api_key', 'foo');
    }

    public function test_it_can_be_initiated(): void
    {
        $printOne = new PrintOne();

        $this->assertInstanceOf(PrintOne::class, $printOne);
    }

    public function test_it_can_retrieve_templates(): void
    {
        $fakeResponse = [
            'data' => [
                [
                    'id' => 'tmpl_a8763477-2430-4034-880b-668604e61abb',
                    'name' => 'voorkant',
                    'format' => 'POSTCARD_A6',
                    'version' => 6,
                    'updatedAt' => '2022-09-27T14:48:00.514Z',
                ],
                [
                    'id' => 'tmpl_bf02958a-6495-4432-bc10-4c64d30c635b',
                    'name' => 'nieuwe_woning_achterkant',
                    'format' => 'POSTCARD_A6',
                    'version' => 26,
                    'updatedAt' => '2022-08-19T08:25:27.399Z',
                ],
                [
                    'id' => 'tmpl_901974e2-d535-444e-9ec1-c729c2c62146',
                    'name' => 'nieuwe_woning_voorkant',
                    'format' => 'POSTCARD_A6',
                    'version' => 3,
                    'updatedAt' => '2022-08-18T14:39:13.629Z',
                ],
                [
                    'id' => 'tmpl_3c137cc0-e722-4a38-89b0-a824114e0a7e',
                    'name' => 'achterkant',
                    'format' => 'POSTCARD_A6',
                    'version' => 3,
                    'updatedAt' => '2022-07-12T09:40:26.504Z',
                ],
            ],
            'total' => 4,
            'page' => 1,
            'pageSize' => 10,
            'pages' => 1,
            'filters' => [],
        ];

        Http::fake([
            'https://api.print.one/v1/templates?*' => Http::response($fakeResponse),
        ]);

        $templates = PrintOne::templates(page: 1, size: 50);

        $this->assertInstanceOf(Collection::class, $templates);
        $this->assertContainsOnlyInstancesOf(Template::class, $templates);

        $this->assertEquals($fakeResponse['data'][0]['id'], $templates[0]->id);
        $this->assertEquals($fakeResponse['data'][0]['name'], $templates[0]->name);
        $this->assertEquals($fakeResponse['data'][0]['format'], $templates[0]->format);
        $this->assertEquals($fakeResponse['data'][0]['version'], $templates[0]->version);
        $this->assertTrue(Carbon::parse($fakeResponse['data'][0]['updatedAt'], 'UTC')->eq($templates[0]->updatedAt));

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('X-Api-Key', 'foo') && $request->url(
                ) == 'https://api.print.one/v1/templates?page=1&size=50';
        });
    }

    public function test_it_throws_exception_when_fetching_templates_fails()
    {
        Http::fake([
            'https://api.print.one/v1/templates?*' => Http::response(status: Response::HTTP_INTERNAL_SERVER_ERROR),
        ]);

        $this->expectException(CouldNotFetchTemplates::class);
        $this->expectExceptionMessage('Something went wrong while fetching the templates from the Print.one API.');

        PrintOne::templates(page: 1, size: 50);
    }

    public function test_it_can_order_a_card(): void
    {
        $fakeResponse = [
            'id' => 'ord_25a36175-52c8-4c81-96fc-1d829af9ffee',
            'sender' => [
                'city' => 'string',
                'name' => 'string',
                'address' => 'string',
                'country' => 'string',
                'postalCode' => 'string',
            ],
            'recipient' => [
                'city' => 'string',
                'name' => 'string',
                'address' => 'string',
                'country' => 'string',
                'postalCode' => 'string',
            ],
            'mergeVariables' => [
                'lastName' => 'Duck',
                'firstName' => 'Donald',
            ],
            'billingId' => 'string',
            'isBillable' => false,
            'status' => 'order_created',
            'format' => 'POSTCARD_A6',
            'customerId' => '',
            'createdAt' => '2022-10-06T08:49:40.368Z',
            'updatedAt' => '2022-10-06T08:49:40.368Z',
            'pages' => [
                [
                    'id' => '90238dbd-623e-472a-892d-0ae7dccd5d57',
                    'templateId' => 'tmpl_a8763477-2430-4034-880b-668604e61abb',
                    'order' => 1,
                    'cardId' => 'ord_25a36175-52c8-4c81-96fc-1d829af9ffee',
                    'createdAt' => '2022-10-06T08:49:40.368Z',
                    'updatedAt' => '2022-10-06T08:49:40.368Z',
                ],
                [
                    'id' => 'be52a381-8be8-4ccc-8c98-f73a53cf0d3b',
                    'templateId' => 'tmpl_a8763477-2430-4034-880b-668604e61abb',
                    'order' => 2,
                    'cardId' => 'ord_25a36175-52c8-4c81-96fc-1d829af9ffee',
                    'createdAt' => '2022-10-06T08:49:40.368Z',
                    'updatedAt' => '2022-10-06T08:49:40.368Z',
                ],
            ],
        ];

        Http::fake([
            'https://api.print.one/v1/orders' => Http::response($fakeResponse),
        ]);

        [$templateFront, $templateBack, $mergeVariables, $sender, $recipient] = $this->createOrder();

        $postcard = new Postcard(front: $templateFront, back: $templateBack);
        $order = PrintOne::order(
            $postcard,
            mergeVariables: $mergeVariables,
            sender: $sender,
            recipient: $recipient
        );

        Http::assertSent(
            fn(Request $request) => $request->url() === 'https://api.print.one/v1/orders' &&
                $request['pages'][0] === $templateFront->id &&
                $request['sender'] === $sender->toArray() &&
                $request['recipient'] === $recipient->toArray() &&
                $request['format'] === $templateFront->format
        );

        $this->assertInstanceOf(Order::class, $order);

        $this->assertEquals($fakeResponse['id'], $order->id);
        $this->assertEquals('order_created', $order->status);
        $this->assertTrue(Carbon::parse($fakeResponse['createdAt'], 'UTC')->eq($order->createdAt));
        $this->assertFalse($order->isBillable);
    }

    public function test_it_throws_exception_when_order_is_invalid(): void
    {
        $fakeResponse = [
            'error' => 'The order is invalid.',
            'errors' => [
                [
                    'type' => 'template',
                    'message' => "'tmpl_a8763477-2430-4034-880b-668604e61abb' has format: 'POSTCARD_A6' while you have provided: 'POSTCARD_A5'.",
                ],
                [
                    'type' => 'template',
                    'message' => "'tmpl_a8763477-2430-4034-880b-668604e61abb' has format: 'POSTCARD_A6' while you have provided: 'POSTCARD_A5'.",
                ],
            ],
        ];

        Http::fake([
            'https://api.print.one/v1/orders' => Http::response($fakeResponse, status: Response::HTTP_BAD_REQUEST),
        ]);

        [$templateFront, $templateBack, $mergeVariables, $sender, $recipient] = $this->createOrder();

        $this->expectException(CouldNotPlaceOrder::class);
        $this->expectExceptionMessage(
            "The order is invalid: 'tmpl_a8763477-2430-4034-880b-668604e61abb' has format: 'POSTCARD_A6' while you have provided: 'POSTCARD_A5'."
        );

        $postcard = new Postcard(front: $templateFront, back: $templateBack);
        PrintOne::order(
            postcard: $postcard,
            mergeVariables: $mergeVariables,
            sender: $sender,
            recipient: $recipient
        );
    }

    public function test_it_throws_exception_when_placing_order_fails(): void
    {
        Http::fake([
            'https://api.print.one/v1/orders' => Http::response(status: Response::HTTP_INTERNAL_SERVER_ERROR),
        ]);

        [$templateFront, $templateBack, $mergeVariables, $sender, $recipient] = $this->createOrder();

        $this->expectException(CouldNotPlaceOrder::class);
        $this->expectExceptionMessage('Something went wrong while placing the order in the Print.one API.');

        $postcard = new Postcard(front: $templateFront, back: $templateBack);
        PrintOne::order(
            postcard: $postcard,
            mergeVariables: $mergeVariables,
            sender: $sender,
            recipient: $recipient
        );
    }

    public function test_it_can_fetch_template_previews(): void
    {
        $imageString = file_get_contents(__DIR__ . '/images/card-preview.png');

        Http::fake([
            'https://api.print.one/v1/templates/preview/*' => Http::response('3c9d6b72-48a5-41f3-bcac-a5ffdd6eaede'),
            'https://api.print.one/v1/storage/template/preview/*' => Http::response($imageString, 200, [
                'Content-type' => 'image/png',
                'Content-Disposition' => 'attachment; filename=3c9d6b72-48a5-41f3-bcac-a5ffdd6eaede.png',
            ]),
        ]);

        $template = Template::fromArray([
            'id' => 'tmpl_a8763477-2430-4034-880b-668604e61abb',
            'name' => 'voorkant',
            'format' => 'POSTCARD_A6',
            'version' => 6,
            'updatedAt' => '2022-09-27T14:48:00.514Z',
        ]);

        $previewImage = PrintOne::preview(template: $template);

        $this->assertIsString($previewImage);
        $this->assertEquals($imageString, $previewImage);

        Http::assertSent(
            fn(Request $request) => $request->url(
                ) === 'https://api.print.one/v1/templates/preview/tmpl_a8763477-2430-4034-880b-668604e61abb/6'
        );
    }

    public function test_it_throws_exception_when_fetching_preview_fails(): void
    {
        Http::fake([
            'https://api.print.one/v1/templates/preview/*' => Http::response('3c9d6b72-48a5-41f3-bcac-a5ffdd6eaede'),
            'https://api.print.one/v1/storage/template/preview/*' => Http::response(
                status: Response::HTTP_INTERNAL_SERVER_ERROR
            ),
        ]);

        $template = Template::fromArray([
            'id' => 'tmpl_a8763477-2430-4034-880b-668604e61abb',
            'name' => 'voorkant',
            'format' => 'POSTCARD_A6',
            'version' => 6,
            'updatedAt' => '2022-09-27T14:48:00.514Z',
        ]);

        $this->expectException(CouldNotFetchPreview::class);
        $this->expectExceptionMessage('Something went wrong while fetching the preview from the Print.one API.');

        PrintOne::preview(template: $template, timeout: 0);
    }

    private function createOrder(): array
    {
        $templateFront = Template::fromArray([
            'id' => 'tmpl_a8763477-2430-4034-880b-668604e61abb',
            'name' => 'voorkant',
            'format' => 'POSTCARD_A6',
            'version' => 6,
            'updatedAt' => '2022-09-27T14:48:00.514Z',
        ]);

        $templateBack = Template::fromArray([
            'id' => 'tmpl_a8763477-2430-4034-880b-668604e61abb',
            'name' => 'voorkant',
            'format' => 'POSTCARD_A6',
            'version' => 6,
            'updatedAt' => '2022-09-27T14:48:00.514Z',
        ]);

        $sender = new Address(
            name: 'Nexibi',
            address: 'Sendstreet 10',
            postalCode: '1234 AB',
            city: 'Zwolle',
            country: 'The Netherlands',
        );

        $recipient = new Address(
            name: 'John doe',
            address: 'Receivelane 20',
            postalCode: '9870 YZ',
            city: 'Dalfsen',
            country: 'The Netherlands',
        );

        $mergeVariables = [
            'content' => '<h1>Hello World</h1>',
        ];

        return [$templateFront, $templateBack, $mergeVariables, $sender, $recipient];
    }

    public function test_fake_client(): void
    {
        PrintOne::fake(
            $template = new Template(
                id: Str::uuid(),
                name: "Test Template",
                format: Format::A5,
                version: 1,
                updatedAt: now()
            )
        );

        [$templateFront, $templateBack, $mergeVariables, $sender, $recipient] = $this->createOrder();

        PrintOne::order(
            $postcard = new Postcard(
                front: $templateFront, back: $templateBack
            ),
            mergeVariables: $mergeVariables,
            sender: $sender,
            recipient: $recipient
        );
        PrintOne::assertOrdered($postcard, from: $sender, to: $recipient);

        PrintOne::preview($templateFront);
        PrintOne::assertViewed($templateFront);

        $templates = PrintOne::templates(1, 50);
        $this->assertInstanceOf(Collection::class, $templates);
        $this->assertSame($template, $templates[0]);
    }
}
