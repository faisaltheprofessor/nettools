<?php

namespace App\Support;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;

final class Markdown
{
    /**
     * @throws CommonMarkException
     */
    public static function convert(string $md): string
    {
        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
            // Make permalinks subtle and not intrusive
            'heading_permalink' => [
                'symbol' => '¶',
                'insert' => 'after',
                'min_heading_level' => 2,
                'max_heading_level' => 4,
                'aria_hidden' => true,
            ],
        ];

        $env = new Environment($config);
        $env->addExtension(new CommonMarkCoreExtension);
        $env->addExtension(new GithubFlavoredMarkdownExtension);
        $env->addExtension(new TableExtension);
        $env->addExtension(new TaskListExtension);
        $env->addExtension(new HeadingPermalinkExtension);

        $converter = new CommonMarkConverter($config, $env);

        return (string) $converter->convert($md);
    }
}
