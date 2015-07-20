<?php
/**
 * Copyright (C) 2015 David Young
 *
 * Tests the template factory
 */
namespace Opulence\Views\Factories;
use Opulence\Files\FileSystem;
use Opulence\Files\FileSystemException;
use Opulence\Tests\Views\Mocks\BarBuilder;
use Opulence\Tests\Views\Mocks\FooBuilder;
use Opulence\Views\Template;

class TemplateFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var FileSystem The file system to use in tests */
    private $fileSystem = null;
    /** @var TemplateFactory The template factory to use in tests */
    private $templateFactory = null;

    /**
     * Sets up the tests
     */
    public function setUp()
    {
        $this->fileSystem = new FileSystem();
        $this->templateFactory = new TemplateFactory($this->fileSystem, __DIR__ . "/../files");
    }

    /**
     * Tests aliasing a template path
     */
    public function testAlias()
    {
        $this->templateFactory->alias("foo", "TestWithDefaultTagDelimiters.html");
        $this->assertEquals(
            $this->templateFactory->create("foo"),
            $this->templateFactory->create("TestWithDefaultTagDelimiters.html")
        );
    }

    /**
     * Tests passing in a root directory with a trailing slash
     */
    public function testPassingInRootWithTrailingSlash()
    {
        $template = $this->templateFactory->create("TestWithDefaultTagDelimiters.html");
        $expectedContent = $this->fileSystem->read(__DIR__ . "/../files/TestWithDefaultTagDelimiters.html");
        $this->assertInstanceOf(Template::class, $template);
        $this->assertEquals($expectedContent, $template->getContents());
    }

    /**
     * Tests passing in a root directory without a trailing slash
     */
    public function testPassingInRootWithoutTrailingSlash()
    {
        $template = $this->templateFactory->create("TestWithDefaultTagDelimiters.html");
        $expectedContent = $this->fileSystem->read(__DIR__ . "/../files/TestWithDefaultTagDelimiters.html");
        $this->assertInstanceOf(Template::class, $template);
        $this->assertEquals($expectedContent, $template->getContents());
    }

    /**
     * Tests passing in a template path that does not exist
     */
    public function testPassingInTemplatePathThatDoesNotExist()
    {
        $this->setExpectedException(FileSystemException::class);
        $this->templateFactory->create("doesNotExist.html");
    }

    /**
     * Tests passing in a template path with a preceding slash
     */
    public function testPassingInTemplatePathWithPrecedingSlash()
    {
        $template = $this->templateFactory->create("/TestWithDefaultTagDelimiters.html");
        $expectedContent = $this->fileSystem->read(__DIR__ . "/../files/TestWithDefaultTagDelimiters.html");
        $this->assertInstanceOf(Template::class, $template);
        $this->assertEquals($expectedContent, $template->getContents());
    }

    /**
     * Tests passing in a template path without a preceding slash
     */
    public function testPassingInTemplatePathWithoutPrecedingSlash()
    {
        $template = $this->templateFactory->create("TestWithDefaultTagDelimiters.html");
        $expectedContent = $this->fileSystem->read(__DIR__ . "/../files/TestWithDefaultTagDelimiters.html");
        $this->assertInstanceOf(Template::class, $template);
        $this->assertEquals($expectedContent, $template->getContents());
    }

    /**
     * Tests registering a builder
     */
    public function testRegisteringBuilder()
    {
        $this->templateFactory->registerBuilder("TestWithDefaultTagDelimiters.html", function ()
        {
            return new FooBuilder();
        });
        $template = $this->templateFactory->create("TestWithDefaultTagDelimiters.html");
        $this->assertEquals("bar", $template->getTag("foo"));
    }

    /**
     * Tests registering a builder to an alias
     */
    public function testRegisteringBuilderToAlias()
    {
        $this->templateFactory->alias("foo", "TestWithDefaultTagDelimiters.html");
        $this->templateFactory->registerBuilder("foo", function ()
        {
            return new FooBuilder();
        });
        $template = $this->templateFactory->create("foo");
        $this->assertEquals("bar", $template->getTag("foo"));
    }

    /**
     * Tests registering builders to mix of paths and aliases
     */
    public function testRegisteringBuilderToMixOfPathsAndAliases()
    {
        $this->templateFactory->alias("foo", "TestWithDefaultTagDelimiters.html");
        $this->templateFactory->registerBuilder(["foo", "TestWithCustomTagDelimiters.html"], function ()
        {
            return new FooBuilder();
        });
        $fooTemplate = $this->templateFactory->create("foo");
        $customTagTemplate = $this->templateFactory->create("TestWithCustomTagDelimiters.html");
        $this->assertEquals("bar", $fooTemplate->getTag("foo"));
        $this->assertEquals("bar", $customTagTemplate->getTag("foo"));
    }

    /**
     * Tests registering builders to multiple aliases
     */
    public function testRegisteringBuilderToMultipleAliases()
    {
        $this->templateFactory->alias("foo", "TestWithDefaultTagDelimiters.html");
        $this->templateFactory->alias("bar", "TestWithCustomTagDelimiters.html");
        $this->templateFactory->registerBuilder(["foo", "bar"], function ()
        {
            return new FooBuilder();
        });
        $fooTemplate = $this->templateFactory->create("foo");
        $barTemplate = $this->templateFactory->create("bar");
        $this->assertEquals("bar", $fooTemplate->getTag("foo"));
        $this->assertEquals("bar", $barTemplate->getTag("foo"));
    }

    /**
     * Tests registering builders to multiple paths
     */
    public function testRegisteringBuilderToMultiplePaths()
    {
        $this->templateFactory->registerBuilder(["TestWithDefaultTagDelimiters.html", "TestWithCustomTagDelimiters.html"], function ()
        {
            return new FooBuilder();
        });
        $defaultTagsTemplate = $this->templateFactory->create("TestWithDefaultTagDelimiters.html");
        $customTagsTemplate = $this->templateFactory->create("TestWithCustomTagDelimiters.html");
        $this->assertEquals("bar", $defaultTagsTemplate->getTag("foo"));
        $this->assertEquals("bar", $customTagsTemplate->getTag("foo"));
    }

    /**
     * Tests registering a builder to a path also registers to an alias
     */
    public function testRegisteringBuilderToPathAlsoRegistersToAlias()
    {
        $this->templateFactory->alias("foo", "TestWithDefaultTagDelimiters.html");
        $this->templateFactory->registerBuilder("TestWithDefaultTagDelimiters.html", function ()
        {
            return new FooBuilder();
        });
        $template = $this->templateFactory->create("foo");
        $this->assertEquals("bar", $template->getTag("foo"));
    }

    /**
     * Tests registering multiple builders
     */
    public function testRegisteringMultipleBuilders()
    {
        $this->templateFactory->registerBuilder("TestWithDefaultTagDelimiters.html", function ()
        {
            return new FooBuilder();
        });
        $this->templateFactory->registerBuilder("TestWithDefaultTagDelimiters.html", function ()
        {
            return new BarBuilder();
        });
        $template = $this->templateFactory->create("TestWithDefaultTagDelimiters.html");
        $this->assertEquals("bar", $template->getTag("foo"));
        $this->assertEquals("baz", $template->getTag("bar"));
    }

    /**
     * Tests passing in a root directory without a trailing slash
     */
    public function testSettingRootWithoutTrailingSlash()
    {
        $this->templateFactory->setRootTemplateDirectory(__DIR__ . "/../files");
        $this->testPassingInRootWithoutTrailingSlash();
    }
}