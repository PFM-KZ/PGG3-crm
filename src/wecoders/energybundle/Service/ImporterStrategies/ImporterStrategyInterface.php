<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

interface ImporterStrategyInterface
{
    public function getAbsoluteUploadDirectoryPath($kernelRootDir);
    public function getCode();
    public function getValue();
    public function load($fullPathToFile, $filename);
    public function validate($objects);
    public function save($objects);
    public function hydrate($rows);
}