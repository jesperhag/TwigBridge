<?php

namespace TwigBridge;

use Illuminate\Foundation\Application;
use Twig_Environment;
use Twig_Lexer;

class TwigBridge
{
    /**
     * @var string TwigBridge version
     */
    const VERSION = '0.1.0';

    protected $app;
    protected $paths = array();
    protected $options = array();
    protected $extension;
    protected $extensions;
    protected $lexer;

    public function __construct(Application $app)
    {
        $this->app        = $app;
        $this->paths      = $app['config']->get('view.paths', array());
        $this->extension  = $app['config']->get('twigbridge::extension');
        $this->extensions = $app['config']->get('twigbridge::extensions', array());

        $this->setOptions($app['config']->get('twigbridge::twig', array()));
    }

    public function getPaths()
    {
        return $this->paths;
    }

    public function setPaths(array $paths)
    {
        $this->paths = $paths;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options)
    {
        // Check whether we have the cache path set
        if (!isset($options['cache']) OR $options['cache'] === null) {

            // No cache path set for Twig, lets set to the Laravel views storage folder
            $options['cache'] = $this->app['path'].'/storage/views/twig';
        }

        $this->options = $options;
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function setExtension($extension)
    {
        $this->extension = $extension;
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    public function setExtensions(array $extensions)
    {
        $this->extensions = $extensions;
    }

    public function getLexer(Twig_Environment $twig)
    {
        if ($this->lexer !== null) {
            return $this->lexer;
        }

        $delimiters = $this->app['config']->get('twigbridge::delimiters', array(
            'tag_comment'  => array('{#', '#}'),
            'tag_block'    => array('{%', '%}'),
            'tag_variable' => array('{{', '}}'),
        ));

        $this->setLexer($twig, $delimiters);

        return $this->lexer;
    }

    public function setLexer(Twig_Environment $twig, array $delimiters)
    {
        $this->lexer = new Twig_Lexer($twig, $delimiters);
    }

    public function getTwig()
    {
        $loader = new Twig\Loader\Filesystem($this->paths, $this->extension);
        $twig   = new Twig_Environment($loader, $this->options);

        // Allow template tags to be changed
        $twig->setLexer($this->getLexer($twig));

        // Load extensions
        foreach ($this->getExtensions() as $extension) {

            // We support both a closure and class based extension
            $extension = (!is_callable($extension)) ? new $extension($this->app, $twig) : $extension($this->app, $twig);

            // Add extension to twig
            $twig->addExtension($extension);
        }

        return $twig;
    }
}