<?php

namespace CrowdSecBouncer;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
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
     * @param string $path
     * @param string $templatesDir
     * @param array $options
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
