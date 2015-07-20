<?php
/**
 * Copyright (C) 2015 David Young
 *
 * Defines the interface for view builders to implement
 */
namespace Opulence\Views;

interface IBuilder
{
    /**
     * Builds a template or a part of a template
     * Useful for centralizing creation of common components in templates
     *
     * @param ITemplate $template The template to build
     * @return ITemplate The built template
     */
    public function build(ITemplate $template);
}