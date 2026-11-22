<?php

// Стиль проверяется только для собственного кода: /local (модуль, компоненты, шаблон сайта), тесты, docker.
// Ядро (/bitrix) в репозиторий не входит и не проверяется.
$finder = new PhpCsFixer\Finder()
    ->in([__DIR__ . '/local', __DIR__ . '/tests', __DIR__ . '/docker'])
    ->exclude(['js'])
;

return new PhpCsFixer\Config()
    ->setRules([
        '@Symfony' => true,
        // @var перед include/глобальными переменными Битрикса нужен PHPStan — не превращать в обычный комментарий.
        'phpdoc_to_comment' => ['ignored_tags' => ['var']],
    ])
    ->setFinder($finder)
;
