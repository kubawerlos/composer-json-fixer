<?php

declare(strict_types = 1);

namespace ComposerJsonFixer;

class Runner
{
    /** @var ComposerWrapper */
    private $composerWrapper;

    /** @var JsonFile */
    private $jsonFile;

    /** @var Updater */
    private $updater;

    public function __construct(ComposerWrapper $composerWrapper, JsonFile $jsonFile, Updater $updater)
    {
        $this->composerWrapper = $composerWrapper;
        $this->jsonFile        = $jsonFile;
        $this->updater         = $updater;
    }

    public function fix() : void
    {
        $this->composerWrapper->callValidate();

        $fixerFactory = new FixerFactory();
        $properties   = $this->jsonFile->data();

        foreach ($fixerFactory->fixers() as $fixer) {
            $properties = $fixer->fix($properties);
        }

        $this->jsonFile->update($properties);
    }

    /**
     * @return bool
     */
    public function hasAnythingBeenFixed()
    {
        return $this->jsonFile->isModified();
    }

    /**
     * @return string
     */
    public function diff()
    {
        return $this->jsonFile->diff();
    }

    public function runUpdates() : void
    {
        $this->updater->update();
    }

    public function save() : void
    {
        $this->jsonFile->save();
    }
}
