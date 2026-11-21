<?php

/**
 * Минимальные стабы API 1С-Битрикс для PHPStan: модуль main.
 *
 * Ядро в репозиторий не входит, а пригодных стабов на Packagist нет, поэтому здесь объявлены
 * только классы, методы и функции, которые вызывает код /local и tests. Сигнатуры сверены с ядром
 * main 26.150.0 и сужены типами там, где ядро возвращает известный тип. Тел у методов нет.
 * Модули iblock, catalog, sale — в iblock.stub.php, catalog.stub.php, sale.stub.php.
 */

namespace {
    const B_PROLOG_INCLUDED = true;
    const SITE_ID = 's1';
    const LANGUAGE_ID = 'ru';

    /** @return never */
    function LocalRedirect(string $url, bool $skip_security_check = false, string $status = '302 Found'): void
    {
    }

    function bitrix_sessid(): string
    {
    }

    function check_bitrix_sessid(string $varname = 'sessid'): bool
    {
    }

    /** @return bool|null */
    function CopyDirFiles(string $path_from, string $path_to, bool $ReWrite = true, bool $Recursive = false, bool $bDeleteAfterCopy = false, string $strExclude = '')
    {
    }

    /** @return bool|null */
    function DeleteDirFilesEx(string $path)
    {
    }

    /** @param list<string> $arExept */
    function DeleteDirFiles(string $frDir, string $toDir, array $arExept = []): void
    {
    }

    function RegisterModule(string $id): void
    {
    }

    function UnRegisterModule(string $id): void
    {
    }

    class CModule
    {
        /** @var string */
        public $MODULE_ID;
        /** @var string */
        public $MODULE_VERSION;
        /** @var string */
        public $MODULE_VERSION_DATE;
        /** @var string */
        public $MODULE_NAME;
        /** @var string */
        public $MODULE_DESCRIPTION;
        /** @var string */
        public $PARTNER_NAME;
        /** @var string */
        public $PARTNER_URI;

        /** @return CModule|false */
        public static function CreateModuleObject(string $moduleName)
        {
        }

        public function DoInstall()
        {
        }

        public function DoUninstall()
        {
        }
    }

    class CMain
    {
        /** @var string */
        public $LAST_ERROR = '';

        public function SetTitle(string $title, array $arOptions = []): void
        {
        }

        public function ShowTitle(string|false $property_name = 'title', bool $strip_tags = true): void
        {
        }

        public function ShowHeadStrings(): void
        {
        }

        public function ShowHeadScripts(): void
        {
        }

        public function ShowCSS(bool $cMaxStylesCnt = true, bool $bXhtmlStyle = true): void
        {
        }

        public function GetCurPage(?bool $get_index_page = null): string
        {
        }

        /** @param callable|array{0: class-string|object, 1: string} $callback */
        public function AddBufferContent($callback, mixed ...$args): void
        {
        }

        public function SetGroupRight(string $module_id, int $group_id, string $right, string|false $site_id = false): void
        {
        }

        /** @param list<int>|false $groups */
        public function GetGroupRight(string $module_id, array|false $groups = false, string $use_default_level = 'Y', string $max_right_for_super_admin = 'N', string|false $site_id = false): string
        {
        }

        /**
         * @param array{0: string, 1: string} $path
         * @param array<int|string, string>   $arPermissions
         */
        public function SetFileAccessPermission(array $path, array $arPermissions, bool $bOverWrite = true): bool
        {
        }

        /**
         * @param array{0: string, 1: string} $path
         * @param list<int>|false             $groups
         */
        public function GetFileAccessPermission(array $path, array|false $groups = false, bool $task_mode = false): string
        {
        }

        public function IncludeComponent(string $componentName, string $componentTemplate, array $arParams = [], CBitrixComponent|false|null $parentComponent = null, array $arFunctionParams = [], bool $returnResult = false)
        {
        }
    }

    class CUser
    {
        public static function Delete(int|string $id): bool
        {
        }

        /** @var string */
        public $LAST_ERROR = '';

        /**
         * @param array<string, mixed> $arFields
         *
         * @return int|string|false
         */
        public function Add(array $arFields)
        {
        }

        /** @param array<string, mixed> $arFields */
        public function Update(int|string $ID, array $arFields, bool $authActions = true): bool
        {
        }

        /** @return array<string, mixed>|true */
        public function Login(string $login, string $password, string $remember = 'N', string $password_original = 'Y')
        {
        }

        public function Authorize(int $id, bool $bSave = false, bool $bUpdate = true, ?string $applicationId = null): bool
        {
        }

        public function Logout(): void
        {
        }

        public function IsAuthorized(): bool
        {
        }

        public function IsAdmin(): bool
        {
        }

        /** @return int|string|null */
        public function GetID()
        {
        }

        /** @return list<int|string> */
        public static function GetUserGroup(int $ID): array
        {
        }

        /** @return list<int|string> */
        public function GetUserGroupArray(): array
        {
        }
    }

    class CGroup
    {
        /** @var string */
        public $LAST_ERROR = '';

        /**
         * @param array<string, mixed> $arFields
         *
         * @return int|string|false
         */
        public function Add(array $arFields)
        {
        }

        /** @return bool|object */
        public static function Delete(int|string $ID)
        {
        }
    }

    class CSite
    {
        /** @var string */
        public $LAST_ERROR = '';

        /** @param array<string, mixed> $arFields */
        public function Update(string $ID, array $arFields): bool
        {
        }
    }

    class CFile
    {
        /**
         * @param array<array-key, mixed> $arFile
         *
         * @return int|false
         */
        public static function SaveFile(array $arFile, string $strSavePath, bool $forceRandom = false, bool $skipExtension = false, string $dirAdd = '')
        {
        }

        /** @return array<string, mixed>|false|null */
        public static function MakeFileArray(mixed $path, string|false $mimetype = false, bool $skipInternal = false, string $external_id = '')
        {
        }

        /** @return string|false */
        public static function GetPath(int|string|null $img_id)
        {
        }

        public static function Delete(int|string $ID): void
        {
        }

        /**
         * @param array<string, string> $arOrder
         * @param array<string, mixed>  $arFilter
         */
        public static function GetList(array $arOrder = [], array $arFilter = []): CDBResult
        {
        }
    }

    class CDBResult
    {
        /** @return array<string, mixed>|false */
        public function Fetch()
        {
        }
    }

    class CHTTP
    {
        public static function SetStatus(string $status): void
        {
        }
    }

    class CUtil
    {
        /** @param array<string, mixed> $params */
        public static function translit(string $str, string $lang, array $params = []): string
        {
        }
    }

    class CBitrixComponent
    {
        /** @var array<string, mixed> */
        public $arParams = [];
        /** @var array<string, mixed> */
        public $arResult = [];

        public function executeComponent()
        {
        }

        public function includeComponentTemplate(string $templatePage = '', string $customTemplatePath = ''): void
        {
        }
    }

    class CComponentEngine
    {
        public function __construct(?CBitrixComponent $component = null)
        {
        }

        /**
         * @param array<string, string>   $arUrlTemplates
         * @param array<array-key, mixed> $arVariables
         *
         * @return string|false
         */
        public function guessComponentPath(string $folder404, array $arUrlTemplates, array &$arVariables, string|false $requestURL = false)
        {
        }
    }
}

namespace Bitrix\Main {
    class SystemException extends \Exception
    {
    }

    class ArgumentException extends SystemException
    {
    }

    class Loader
    {
        public static function includeModule(string $moduleName): bool
        {
        }

        /** @throws LoaderException */
        public static function requireModule(string $moduleName): void
        {
        }
    }

    class LoaderException extends SystemException
    {
    }

    class EventManager
    {
        public static function getInstance(): self
        {
        }

        public function registerEventHandler(string $fromModuleId, string $eventType, string $toModuleId, string $toClass = '', string $toMethod = '', int $sort = 100, string $toPath = '', array $toMethodArg = []): void
        {
        }

        public function unRegisterEventHandler(string $fromModuleId, string $eventType, string $toModuleId, string $toClass = '', string $toMethod = '', string $toPath = '', array $toMethodArg = []): void
        {
        }

        /**
         * @param list<string> $filter
         *
         * @return array<array-key, array<string, mixed>>
         */
        public function findEventHandlers(string $eventModuleId, string $eventType, array $filter = []): array
        {
        }
    }

    class ModuleManager
    {
        public static function isModuleInstalled(string $moduleName): bool
        {
        }

        public static function registerModule(string $moduleName): void
        {
        }

        public static function unRegisterModule(string $moduleName): void
        {
        }
    }

    class Error
    {
        public function __construct(string $message, int|string $code = 0, mixed $customData = null)
        {
        }

        public function getMessage(): string
        {
        }

        /** @return int|string */
        public function getCode()
        {
        }
    }

    class ErrorCollection implements \IteratorAggregate, \Countable
    {
        /** @return \ArrayIterator<int, Error> */
        public function getIterator(): \ArrayIterator
        {
        }

        public function count(): int
        {
        }

        /** @return list<Error> */
        public function getValues(): array
        {
        }

        public function current(): ?Error
        {
        }
    }

    class Result
    {
        public function isSuccess(): bool
        {
        }

        /** @return list<string> */
        public function getErrorMessages(): array
        {
        }

        /** @return list<Error> */
        public function getErrors(): array
        {
        }

        public function getErrorCollection(): ErrorCollection
        {
        }

        public function addError(Error $error): static
        {
        }

        /** @param array<array-key, Error> $errors */
        public function addErrors(array $errors): static
        {
        }

        /** @return array<array-key, mixed> */
        public function getData(): array
        {
        }

        /** @param array<array-key, mixed> $data */
        public function setData(array $data): static
        {
        }
    }

    class Application
    {
        public static function getInstance(): HttpApplication
        {
        }

        public static function getConnection(string $name = ''): DB\Connection
        {
        }

        public static function getDocumentRoot(): string
        {
        }

        public function getContext(): Context
        {
        }

        public function getSession(): Session\SessionInterface
        {
        }
    }

    class HttpApplication extends Application
    {
    }

    class Context
    {
        public static function getCurrent(): Context
        {
        }

        public function getRequest(): HttpRequest
        {
        }

        public function getServer(): Server
        {
        }

        public function getResponse(): HttpResponse
        {
        }
    }

    class Server
    {
        /** @param array<string, mixed> $arServer */
        public function __construct(array $arServer)
        {
        }

        public function getHttpHost(): string
        {
        }

        public function get(string $name)
        {
        }
    }

    /**
     * @implements \IteratorAggregate<string, mixed>
     */
    class ParameterDictionary implements \IteratorAggregate
    {
        public function get(string $name)
        {
        }

        /** @return array<array-key, mixed> */
        public function toArray(): array
        {
        }

        /** @return \ArrayIterator<string, mixed> */
        public function getIterator(): \ArrayIterator
        {
        }
    }

    class HttpRequest extends Request
    {
        /**
         * @param array<string, mixed> $queryString
         * @param array<string, mixed> $postData
         * @param array<string, mixed> $files
         * @param array<string, mixed> $cookies
         */
        public function __construct(Server $server, array $queryString, array $postData, array $files, array $cookies)
        {
        }

        public function getQuery(string $name)
        {
        }

        public function getPost(string $name)
        {
        }

        public function getFile(string $name)
        {
        }

        public function getQueryList(): ParameterDictionary
        {
        }

        public function getPostList(): ParameterDictionary
        {
        }

        public function getRequestUri(): ?string
        {
        }

        public function getHttpHost(): string
        {
        }

        public function getHeader(string $name): ?string
        {
        }

        public function isPost(): bool
        {
        }

        public function isHttps(): bool
        {
        }

        public function getRequestMethod(): string
        {
        }

        public function getServer(): Server
        {
        }
    }

    class Response
    {
        /** @return $this */
        public function setStatus(int|string $status)
        {
        }
    }

    class HttpResponse extends Response
    {
    }

    class UrlRewriter
    {
        /** @param array<string, mixed> $fields */
        public static function add(string $siteId, array $fields): void
        {
        }

        /** @param array<string, mixed> $filter */
        public static function delete(string $siteId, array $filter): void
        {
        }

        /**
         * @param array<string, mixed> $filter
         *
         * @return list<array<string, mixed>>
         */
        public static function getList(string $siteId, array $filter = [], array $order = []): array
        {
        }
    }

    class UserTable extends ORM\Data\DataManager
    {
    }

    class GroupTable extends ORM\Data\DataManager
    {
    }

    class UserGroupTable extends ORM\Data\DataManager
    {
    }

    class SiteTemplateTable extends ORM\Data\DataManager
    {
    }
}

namespace Bitrix\Main\Session {
    interface SessionInterface
    {
        public function has(string $name): bool;

        public function get(string $name);

        public function set(string $name, mixed $value): void;

        public function remove(string $name): void;

        public function isStarted(): bool;

        public function clear(): void;
    }
}

namespace Bitrix\Main\Config {
    class Option
    {
        public static function get(string $moduleId, string $name, string $default = '', string|false $siteId = false): string
        {
        }

        public static function set(string $moduleId, string $name, string $value = '', string $siteId = ''): void
        {
        }

        /** @param array<string, mixed> $filter */
        public static function delete(string $moduleId, array $filter = []): void
        {
        }
    }

    /**
     * @implements \ArrayAccess<string, mixed>
     */
    class Configuration implements \ArrayAccess
    {
        public static function getInstance(?string $moduleId = null): Configuration
        {
        }

        public function get(string $name)
        {
        }

        public function offsetExists(mixed $offset): bool
        {
        }

        public function offsetGet(mixed $offset): mixed
        {
        }

        public function offsetSet(mixed $offset, mixed $value): void
        {
        }

        public function offsetUnset(mixed $offset): void
        {
        }
    }
}

namespace Bitrix\Main\Localization {
    class Loc
    {
        public static function loadMessages(string $file): void
        {
        }

        /** @param array<string, string>|null $replace */
        public static function getMessage(string $code, ?array $replace = null, ?string $language = null): ?string
        {
        }
    }
}

namespace Bitrix\Main\Security {
    class Password
    {
        public static function equals(string $hash, string $password, bool $original = true): bool
        {
        }

        public static function hash(string $password): string
        {
        }
    }
}

namespace Bitrix\Main\Page {
    class Asset
    {
        public static function getInstance(): Asset
        {
        }

        public function addJs(string $path, bool $additional = false): bool
        {
        }

        public function addCss(string $path, bool|string|null $additional = false): bool
        {
        }
    }
}

namespace Bitrix\Main\DI {
    class ServiceLocator
    {
        public static function getInstance(): ServiceLocator
        {
        }

        public function has(string $code): bool
        {
        }

        public function get(string $code)
        {
        }

        public function addInstance(string $code, object $service): void
        {
        }
    }
}

namespace Bitrix\Main\Type {
    class Date
    {
        public function format(string $format): string
        {
        }

        public function getTimestamp(): int
        {
        }

        public function __toString(): string
        {
        }
    }

    class DateTime extends Date
    {
        public function __construct(?string $time = null, ?string $format = null, ?\DateTimeZone $timezone = null)
        {
        }

        public static function createFromTimestamp(int $timestamp): DateTime
        {
        }

        /** @return $this */
        public function setTimeZone(\DateTimeZone $timezone)
        {
        }
    }
}

namespace Bitrix\Main\DB {
    class SqlQueryException extends \Bitrix\Main\SystemException
    {
    }

    class TransactionException extends \Bitrix\Main\SystemException
    {
    }

    abstract class SqlHelper
    {
        abstract public function forSql(string $value, int $maxLength = 0): string;

        public function quote(string $identifier): string
        {
        }

        public function convertToDbDateTime(?\Bitrix\Main\Type\DateTime $value = null): string
        {
        }
    }

    class Result
    {
        /** @return array<string, mixed>|false */
        public function fetch()
        {
        }

        /** @return list<array<string, mixed>> */
        public function fetchAll(): array
        {
        }
    }

    abstract class Connection
    {
        public function getDatabase(): string
        {
        }

        public function getSqlHelper(): SqlHelper
        {
        }

        public function query(string $sql): Result
        {
        }

        public function queryScalar(string $sql, ?array $binds = null)
        {
        }

        public function queryExecute(string $sql, ?array $binds = null): void
        {
        }

        abstract public function isTableExists(string $tableName): bool;

        public function truncateTable(string $tableName): Result
        {
        }

        abstract public function startTransaction(): void;

        abstract public function commitTransaction(): void;

        abstract public function rollbackTransaction(): void;

        public function getInsertedId(): int
        {
        }
    }
}

namespace Bitrix\Main\ORM\Data {
    use Bitrix\Main\ORM\Query\Query;

    class Result extends \Bitrix\Main\Result
    {
    }

    class AddResult extends Result
    {
        /** @return int|string|array<string, mixed> */
        public function getId()
        {
        }
    }

    class UpdateResult extends Result
    {
    }

    class DeleteResult extends Result
    {
    }

    abstract class DataManager
    {
        /** @return string */
        public static function getTableName()
        {
        }

        /** @return array<array-key, mixed> */
        public static function getMap()
        {
        }

        /** @param array<string, mixed> $parameters */
        public static function getList(array $parameters = []): \Bitrix\Main\ORM\Query\Result
        {
        }

        /** @param array<array-key, mixed>|\Bitrix\Main\ORM\Query\Filter\ConditionTree $filter */
        public static function getCount($filter = [], array $cache = []): int
        {
        }

        public static function query(): Query
        {
        }

        /** @param array<string, mixed> $data */
        public static function add(array $data): AddResult
        {
        }

        /**
         * @param int|string|array<string, mixed> $primary
         * @param array<string, mixed>            $data
         */
        public static function update($primary, array $data): UpdateResult
        {
        }

        /** @param int|string|array<string, mixed> $primary */
        public static function delete($primary): DeleteResult
        {
        }

        /** @param int|string|array<string, mixed> $id */
        public static function getById($id): \Bitrix\Main\ORM\Query\Result
        {
        }
    }
}

namespace Bitrix\Main\ORM\Query {
    class Result
    {
        /** @return array<string, mixed>|false */
        public function fetch()
        {
        }

        /** @return list<array<string, mixed>> */
        public function fetchAll(): array
        {
        }

        public function getCount(): int
        {
        }
    }

    class Join
    {
        public static function on(string $definition1, string $definition2): Filter\ConditionTree
        {
        }
    }

    class Query extends Filter\ConditionTree
    {
        /** @return int|string|null */
        public function queryCountTotal()
        {
        }

        /** @param array<array-key, mixed> $select */
        public function setSelect(array $select): static
        {
        }

        /** @param array<array-key, mixed>|string $order */
        public function setOrder($order): static
        {
        }

        public function enablePrivateFields(): static
        {
        }

        public function setLimit(?int $limit): static
        {
        }

        public function setOffset(?int $offset): static
        {
        }

        public static function filter(): Filter\ConditionTree
        {
        }

        /** @param array<array-key, string>|string $group */
        public function setGroup($group): static
        {
        }

        public function countTotal(?bool $count = null): static
        {
        }

        /** @param \Bitrix\Main\ORM\Fields\Field|string $name */
        public function registerRuntimeField($name, mixed $fieldInfo = null): static
        {
        }

        public function exec(): Result
        {
        }

        /** @return array<string, mixed>|false */
        public function fetch()
        {
        }

        /** @return list<array<string, mixed>> */
        public function fetchAll(): array
        {
        }
    }
}

namespace Bitrix\Main\ORM\Query\Filter {
    class ConditionTree
    {
        public function logic(?string $logic = null): static
        {
        }

        public function where(mixed ...$filter): static
        {
        }

        public function whereNot(mixed ...$filter): static
        {
        }

        /** @param array<array-key, mixed>|\Bitrix\Main\ORM\Query\Query $values */
        public function whereIn(string $column, $values): static
        {
        }

        public function whereBetween(string $column, mixed $valueMin, mixed $valueMax): static
        {
        }

        public function whereLike(string $column, string $value): static
        {
        }

        public function whereNull(string $column): static
        {
        }

        public function whereNotNull(string $column): static
        {
        }
    }
}

namespace Bitrix\Main\ORM\Fields {
    abstract class Field
    {
        public function __construct(string $name, array $parameters = [])
        {
        }
    }

    class ScalarField extends Field
    {
        public function configurePrimary(bool $value = true): static
        {
        }

        public function configureAutocomplete(bool $value = true): static
        {
        }

        public function configureRequired(bool $value = true): static
        {
        }

        public function configureNullable(bool $value = true): static
        {
        }

        public function configureColumnName(string $name): static
        {
        }

        public function configureDefaultValue(mixed $value): static
        {
        }
    }

    class IntegerField extends ScalarField
    {
    }

    class StringField extends ScalarField
    {
        public function configureSize(int $size): static
        {
        }
    }

    class TextField extends StringField
    {
    }

    class BooleanField extends ScalarField
    {
        public function configureValues(mixed $falseValue, mixed $trueValue): static
        {
        }
    }

    class DateField extends ScalarField
    {
    }

    class DatetimeField extends DateField
    {
    }

    class ExpressionField extends Field
    {
        /** @param string|list<string> $buildFrom */
        public function __construct(string $name, string $expression, $buildFrom = null, array $parameters = [])
        {
        }
    }
}

namespace Bitrix\Main\ORM\Fields\Relations {
    use Bitrix\Main\ORM\Fields\Field;

    abstract class Relation extends Field
    {
    }

    class Reference extends Relation
    {
        /** @param \Bitrix\Main\ORM\Query\Filter\ConditionTree|array<string, string> $referenceFilter */
        public function __construct(string $name, string $referenceEntity, $referenceFilter, array $parameters = [])
        {
        }

        public function configureJoinType(string $type): static
        {
        }
    }

    class OneToMany extends Relation
    {
        public function __construct(string $name, string $referenceEntity, string $referenceName)
        {
        }

        public function configureJoinType(string $type): static
        {
        }
    }
}

namespace Bitrix\Main\Engine {
    class CurrentUser
    {
        public static function get(): CurrentUser
        {
        }

        /** @return int|string|null */
        public function getId()
        {
        }

        public function isAdmin(): bool
        {
        }

        /** @return list<int|string> */
        public function getUserGroups(): array
        {
        }
    }

    class Action
    {
        public function getName(): string
        {
        }

        public function getController(): Controller
        {
        }
    }

    class Controller
    {
        public function __construct(?\Bitrix\Main\Request $request = null)
        {
        }

        public function getRequest(): \Bitrix\Main\HttpRequest
        {
        }

        public function getCurrentUser(): ?CurrentUser
        {
        }

        /** @return list<ActionFilter\Base> */
        protected function getDefaultPreFilters(): array
        {
        }

        /** @return array<string, mixed> */
        public function configureActions(): array
        {
        }

        protected function processBeforeAction(Action $action): bool
        {
        }

        public function finalizeResponse(\Bitrix\Main\Response $response): void
        {
        }

        protected function addError(\Bitrix\Main\Error $error): static
        {
        }

        /** @return list<\Bitrix\Main\Error> */
        public function getErrors(): array
        {
        }

        public function redirectTo(string $url): Response\Redirect
        {
        }
    }
}

namespace Bitrix\Main {
    class Request
    {
        public function get(string $name): mixed
        {
        }
    }

    class TaskTable extends ORM\Data\DataManager
    {
    }
}

namespace Bitrix\Main\Engine\ActionFilter {
    abstract class Base
    {
    }

    class HttpMethod extends Base
    {
        public const METHOD_GET = 'GET';
        public const METHOD_POST = 'POST';
        public const ERROR_INVALID_HTTP_METHOD = 'invalid_http_method';

        /** @param list<string> $allowedMethods */
        public function __construct(array $allowedMethods = [])
        {
        }
    }

    class Csrf extends Base
    {
        public const ERROR_INVALID_CSRF = 'invalid_csrf';

        public function __construct(bool $enabled = true, string $tokenName = 'sessid', bool $returnNew = true)
        {
        }
    }
}

namespace Bitrix\Main\Engine\Response {
    class Redirect extends \Bitrix\Main\HttpResponse
    {
        public function __construct(string $url, bool $skipSecurity = false)
        {
        }

        public function setSkipSecurity(bool $skipSecurity): static
        {
        }
    }
}
