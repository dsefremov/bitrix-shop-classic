<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Functional;

use Dsefremov\ShopClassic\Tests\Support\Factories;
use Dsefremov\ShopClassic\Tests\Support\TestDatabase;
use Dsefremov\ShopClassic\View\Url;
use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

/**
 * База HTTP-тестов: настоящий запрос через nginx + PHP-FPM к тестовому сайту (порт 8097,
 * база bitrix_shop_classic_test), BrowserKit с cookie-сессией; фабрики пишут в ту же базу.
 * Перед каждым тестом база очищается (TestDatabase::reset), см. docs/testing.md.
 */
abstract class WebTestCase extends TestCase
{
    use Factories;
    public const string BASE_URL = 'http://127.0.0.1:8097';

    protected HttpBrowser $client;

    protected function setUp(): void
    {
        TestDatabase::reset();
        $this->client = $this->newClient();
    }

    protected function newClient(): HttpBrowser
    {
        $base = getenv('TEST_BASE_URL') ?: self::BASE_URL;
        $client = new HttpBrowser(HttpClient::create(['base_uri' => $base, 'max_redirects' => 0, 'timeout' => 30]));
        $client->setServerParameter('HTTP_HOST', (string) parse_url($base, \PHP_URL_HOST).':'.(string) parse_url($base, \PHP_URL_PORT));
        // Редиректы не выполняются сами: тест проверяет их явно.
        $client->followRedirects(false);

        return $client;
    }

    /**
     * Вход через публичную форму /login/ (контроллер Auth модуля, CUser::Login).
     *
     * @param array{email: string, password: string} $user
     */
    protected function login(array $user): void
    {
        $this->client->request('GET', '/login/');
        $this->client->submitForm('Войти', ['email' => $user['email'], 'password' => $user['password']]);
        self::assertSame(302, $this->httpStatus(), 'Вход не удался.');
        self::assertStringStartsNotWith('/login', $this->location(), 'Вход не удался.');
        $this->client->followRedirect();
    }

    protected function get(string $path): Crawler
    {
        return $this->client->request('GET', $path);
    }

    /**
     * POST действия контроллера модуля (action('cart.add') и т. п.) с sessid текущей сессии.
     *
     * @param array<string, int|string> $query
     * @param array<string, mixed>      $data
     */
    protected function postAction(string $action, array $query = [], array $data = [], ?string $sessid = null): Crawler
    {
        $data += ['sessid' => $sessid ?? $this->sessid()];

        return $this->client->request('POST', Url::action($action, $query), $data);
    }

    /**
     * POST на страницу (формы штатных и своих компонентов) с sessid текущей сессии.
     *
     * @param array<string, mixed> $data
     */
    protected function post(string $path, array $data = []): Crawler
    {
        $data += ['sessid' => $this->sessid()];

        return $this->client->request('POST', $path, $data);
    }

    /**
     * sessid текущей сессии: из скрытого поля формы или props React-острова на последней странице;
     * если его там нет (редирект) — со страницы входа или каталога.
     */
    protected function sessid(): string
    {
        try {
            $html = (string) $this->client->getInternalResponse()->getContent();
        } catch (\Throwable) {
            $html = '';
        }
        foreach (['/login/', '/'] as $page) {
            if (1 === preg_match('/name="sessid" (?:id="sessid" )?value="([0-9a-f]{32})"|&quot;csrf&quot;:&quot;([0-9a-f]{32})&quot;/', $html, $m)) {
                return '' !== $m[1] ? $m[1] : $m[2];
            }
            $html = $this->client->request('GET', $page)->html();
        }
        if (1 === preg_match('/name="sessid" (?:id="sessid" )?value="([0-9a-f]{32})"|&quot;csrf&quot;:&quot;([0-9a-f]{32})&quot;/', $html, $m)) {
            return '' !== $m[1] ? $m[1] : $m[2];
        }
        self::fail('sessid не найден на странице.');
    }

    protected function httpStatus(): int
    {
        return $this->client->getInternalResponse()->getStatusCode();
    }

    protected function path(): string
    {
        $uri = $this->client->getRequest()->getUri();
        $query = parse_url($uri, \PHP_URL_QUERY);

        return (string) parse_url($uri, \PHP_URL_PATH).(\is_string($query) ? '?'.$query : '');
    }

    /**
     * Адрес редиректа без схемы и хоста.
     */
    protected function location(): string
    {
        $header = $this->client->getInternalResponse()->getHeader('Location');
        $location = \is_string($header) ? $header : '';
        $query = parse_url($location, \PHP_URL_QUERY);

        return (string) parse_url($location, \PHP_URL_PATH).(\is_string($query) ? '?'.$query : '');
    }

    protected function assertRedirect(string $expected): void
    {
        self::assertContains($this->httpStatus(), [301, 302, 303], 'Ожидался редирект с '.$this->path());
        self::assertSame($expected, $this->location());
    }

    protected function followRedirect(): Crawler
    {
        return $this->client->followRedirect();
    }

    protected function assertStatus(int $expected): void
    {
        self::assertSame($expected, $this->httpStatus(), 'Код ответа для '.$this->path());
    }

    /**
     * @return list<string>
     */
    protected function flashes(string $type): array
    {
        return $this->texts($this->client->getCrawler(), '[data-testid="flash-'.$type.'"]');
    }

    /**
     * @return list<string>
     */
    protected function texts(Crawler $crawler, string $selector): array
    {
        return array_values($crawler->filter($selector)->each(static fn (Crawler $c): string => trim(preg_replace('/[ \t\r\n]+/u', ' ', $c->text()) ?? '')));
    }

    protected function text(string $selector): string
    {
        $node = $this->client->getCrawler()->filter($selector);
        self::assertGreaterThan(0, $node->count(), "Нет элемента {$selector} на {$this->path()}");

        return trim(preg_replace('/[ \t\r\n]+/u', ' ', $node->first()->text()) ?? '');
    }

    /**
     * Props React-острова с первого элемента data-react-component="$component".
     *
     * @return array<string, mixed>
     */
    protected function reactProps(string $component): array
    {
        $node = $this->client->getCrawler()->filter('[data-react-component="'.$component.'"]');
        self::assertGreaterThan(0, $node->count(), "Нет React-острова {$component} на {$this->path()}");
        $props = json_decode((string) $node->first()->attr('data-props'), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($props);

        /** @var array<string, mixed> $props */
        return $props;
    }

    /**
     * Страница входа в административную часть ядра (нет доступа или не вошёл).
     */
    protected function isAdminAuthForm(): bool
    {
        return str_contains((string) $this->client->getInternalResponse()->getContent(), '<title>Авторизация');
    }
}
