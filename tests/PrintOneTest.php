<?php

namespace PrintOne\PrintOne\Tests;

use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PrintOne\PrintOne\DTO\Address;
use PrintOne\PrintOne\DTO\Order;
use PrintOne\PrintOne\DTO\Template;
use PrintOne\PrintOne\Enums\Finish;
use PrintOne\PrintOne\Enums\Format;
use PrintOne\PrintOne\Exceptions\CouldNotFetchPreview;
use PrintOne\PrintOne\Exceptions\CouldNotFetchTemplates;
use PrintOne\PrintOne\Exceptions\CouldNotPlaceOrder;
use PrintOne\PrintOne\Facades\PrintOne;

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
            'https://api.print.one/v2/templates?*' => Http::response($fakeResponse),
        ]);

        $templates = PrintOne::templates(page: 1, limit: 50);

        $this->assertInstanceOf(Collection::class, $templates);
        $this->assertContainsOnlyInstancesOf(Template::class, $templates);

        $this->assertEquals($fakeResponse['data'][0]['id'], $templates[0]->id);
        $this->assertEquals($fakeResponse['data'][0]['name'], $templates[0]->name);
        $this->assertEquals($fakeResponse['data'][0]['format'], $templates[0]->format->value);
        $this->assertEquals($fakeResponse['data'][0]['version'], $templates[0]->version);
        $this->assertTrue(Carbon::parse($fakeResponse['data'][0]['updatedAt'], 'UTC')->eq($templates[0]->updatedAt));

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('X-Api-Key', 'foo') && $request->url(
            ) === 'https://api.print.one/v2/templates?page=1&limit=50';
        });
    }

    public function test_it_throws_exception_when_fetching_templates_fails()
    {
        Http::fake([
            'https://api.print.one/v2/templates?*' => Http::response(status: Response::HTTP_INTERNAL_SERVER_ERROR),
        ]);

        $this->expectException(CouldNotFetchTemplates::class);
        $this->expectExceptionMessage('Something went wrong while fetching the templates from the Print.one API.');

        PrintOne::templates(page: 1, limit: 50);
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
            'templateId' => 'tmpl_a8763477-2430-4034-880b-668604e61abb',
            'finish' => 'GLOSSY',
            'format' => 'POSTCARD_A6',
            'customerId' => '',
            'createdAt' => '2022-10-06T08:49:40.368Z',
            'updatedAt' => '2022-10-06T08:49:40.368Z',
        ];

        Http::fake([
            'https://api.print.one/v2/orders' => Http::response($fakeResponse),
        ]);

        [$templateId, $finish, $mergeVariables, $sender, $recipient] = $this->createOrder();

        $order = PrintOne::order(
            templateId: $templateId,
            finish: $finish,
            mergeVariables: $mergeVariables,
            sender: $sender,
            recipient: $recipient
        );

        //dd($order);

        Http::assertSent(
            // fn (Request $request) => dd($request)
            fn (Request $request) => $request->url() === 'https://api.print.one/v2/orders' &&
                $request['templateId'] === $templateId &&
                $request['finish'] === $finish->value &&
                $request['sender'] === $sender->toArray() &&
                $request['recipient'] === $recipient->toArray()
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
            'https://api.print.one/v2/orders' => Http::response($fakeResponse, status: Response::HTTP_BAD_REQUEST),
        ]);

        [$templateId, $finish, $mergeVariables, $sender, $recipient] = $this->createOrder();

        $this->expectException(CouldNotPlaceOrder::class);
        $this->expectExceptionMessage(
            "The order is invalid: 'tmpl_a8763477-2430-4034-880b-668604e61abb' has format: 'POSTCARD_A6' while you have provided: 'POSTCARD_A5'."
        );

        PrintOne::order(
            templateId: $templateId,
            finish: $finish,
            mergeVariables: $mergeVariables,
            sender: $sender,
            recipient: $recipient
        );
    }

    public function test_it_throws_exception_when_placing_order_fails(): void
    {
        Http::fake([
            'https://api.print.one/v2/orders' => Http::response(status: Response::HTTP_INTERNAL_SERVER_ERROR),
        ]);

        [$templateId, $finish, $mergeVariables, $sender, $recipient] = $this->createOrder();

        $this->expectException(CouldNotPlaceOrder::class);
        $this->expectExceptionMessage('Something went wrong while placing the order in the Print.one API.');

        PrintOne::order(
            templateId: $templateId,
            finish: $finish,
            mergeVariables: $mergeVariables,
            sender: $sender,
            recipient: $recipient
        );
    }

    public function test_it_can_fetch_template_previews(): void
    {
        $imageString = file_get_contents(__DIR__.'/images/card-preview.png');

        Http::fake([
            'https://api.print.one/v2/templates/preview/*' => Http::response(
                [
                    [
                        'url' => 'https://api.print.one/v2/storage/template/preview/3c9d6b72-48a5-41f3-bcac-a5ffdd6eaede',
                    ],
                ]
            ),
            'https://api.print.one/v2/storage/template/preview/3c9d6b72-48a5-41f3-bcac-a5ffdd6eaede' => Http::response($imageString, 200, [
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
            fn (Request $request) => $request->url(
            ) === 'https://api.print.one/v2/templates/preview/tmpl_a8763477-2430-4034-880b-668604e61abb/6'
        );
    }

    public function test_it_throws_exception_when_fetching_preview_fails(): void
    {
        Http::fake([
            'https://api.print.one/v2/templates/preview/*' => Http::response([
                [
                    'url' => 'https://api.print.one/v2/storage/template/preview/3c9d6b72-48a5-41f3-bcac-a5ffdd6eaede',
                ],
            ]),
            'https://api.print.one/v2/storage/template/preview/3c9d6b72-48a5-41f3-bcac-a5ffdd6eaede' => Http::response(
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

        PrintOne::preview(template: $template, retryTimes: 0);
    }

    public function test_fake_client(): void
    {
        PrintOne::fake(
            $template = new Template(
                id: Str::uuid(),
                name: 'Test Template',
                format: Format::A5,
                version: 1,
                updatedAt: now()
            )
        );

        [$templateId, $finish, $mergeVariables, $sender, $recipient] = $this->createOrder();

        PrintOne::order(
            templateId: $templateId,
            finish: $finish,
            mergeVariables: $mergeVariables,
            sender: $sender,
            recipient: $recipient
        );
        PrintOne::assertOrdered(templateId: $templateId, finish: $finish, from: $sender, to: $recipient);

        PrintOne::preview(template: $template);
        PrintOne::assertViewed(template: $template);

        $templates = PrintOne::templates(1, 50);
        $this->assertInstanceOf(Collection::class, $templates);
        $this->assertSame($template, $templates[0]);
    }

    public function test_it_can_fetch_an_order_preview(): void
    {
        $contents = file_get_contents(__DIR__.'/pdfs/order-preview.pdf');

        Http::fake([
            'https://api.print.one/v2/storage/order/preview/*' => Http::response($contents, 200, [
                'Content-type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename=ord_3c9d6b72-48a5-41f3-bcac-a5ffdd6eaede.pdf',
            ]),
        ]
        );

        PrintOne::previewOrder(
            order: $order = new Order(
                id: Str::uuid()->toString(),
                status: 'ready',
                templateId: 'tmpl_a8763477-2430-4034-880b-668604e61abb',
                finish: Finish::GLOSSY,
                createdAt: now(),
                isBillable: false
            )
        );

        Http::assertSent(
            fn (Request $request) => $request->url() === "https://api.print.one/v2/storage/order/preview/{$order->id}"
        );
    }

    public function test_it_can_fetch_an_order(): void
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
            'templateId' => 'tmpl_a8763477-2430-4034-880b-668604e61abb',
            'finish' => 'GLOSSY',
            'billingId' => 'string',
            'isBillable' => false,
            'status' => 'order_created',
            'format' => 'POSTCARD_A6',
            'customerId' => '',
            'createdAt' => '2022-10-06T08:49:40.368Z',
            'updatedAt' => '2022-10-06T08:49:40.368Z',
        ];

        Http::fake([
            'https://api.print.one/v2/orders/ord_25a36175-52c8-4c81-96fc-1d829af9ffee' => Http::response($fakeResponse),
        ]);

        $order = PrintOne::getOrder('ord_25a36175-52c8-4c81-96fc-1d829af9ffee');

        $this->assertInstanceOf(Order::class, $order);

        $this->assertEquals($fakeResponse['id'], $order->id);
        $this->assertEquals('order_created', $order->status);
        $this->assertTrue(Carbon::parse($fakeResponse['createdAt'], 'UTC')->eq($order->createdAt));
        $this->assertFalse($order->isBillable);
    }

    private function createOrder(): array
    {
        $templateId = 'tmpl_a8763477-2430-4034-880b-668604e61abb';

        $finish = Finish::GLOSSY;

        $sender = new Address(
            name: 'print.one',
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

        return [$templateId, $finish, $mergeVariables, $sender, $recipient];
    }
}
