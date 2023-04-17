<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;

/**
 * The template engine.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Template
{
    /** @var TemplateWrapper */
    private $template;

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __construct(string $path, string $templatesDir = Constants::TEMPLATES_DIR, array $options = [])
    {
        $loader = new FilesystemLoader($templatesDir);
        $env = new Environment($loader, $options);
        $this->template = $env->load($path);
    }

    public function render(array $config = []): string
    {
        return $this->template->render(['config' => $config]);
    }
}
