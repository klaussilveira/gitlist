<?php

declare(strict_types=1);

namespace GitList\App\Twig;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new TaskListExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    public function getFilters()
    {
        return [
            new TwigFilter('markdown', [$this, 'markdown']),
        ];
    }

    public function markdown($string): string
    {
        if (!$string) {
            return '';
        }

        return (string) $this->converter->convertToHtml($string);
    }
}
