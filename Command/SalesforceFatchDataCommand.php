<?php

namespace Syncrasy\PimcoreSalesforceBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Syncrasy\PimcoreSalesforceBundle\Model\Mapping;
use Syncrasy\PimcoreSalesforceBundle\Services\ExportService;
use Syncrasy\PimcoreSalesforceBundle\Services\ImportPimcoreService;
use Syncrasy\PimcoreSalesforceBundle\Services\Sfconnect;

class SalesforceFatchDataCommand extends AbstractCommand
{
    public function __construct(string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('salesforce:fetch:data')
            ->setDescription('Fetch data from salesforce')
            ->addOption(
                'id',
                '',
                InputOption::VALUE_REQUIRED

            );

    }

    public  function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getOption('id');
        $mappingObject = Mapping::getById($id);
        if ($mappingObject) {

            $columnAttributeMapping = json_decode($mappingObject->getColumnAttributeMapping(), true);



            $sfObject = new Sfconnect();
            $sObjectType = $mappingObject->getsalesforceobject();
            $sUniqueField = $mappingObject->getSalesforceUniqueField();
            $pimUniqueField = $mappingObject->getPimcoreUniqueField();
            $fieldforsfid = $mappingObject->getFieldforsfid();
            $pimcoreClassType = $mappingObject->getPimcoreClassId();
            $parentFolderId = $mappingObject->getImportFilePathId();
            $exportService = new ImportPimcoreService();
            $exportService->prepareFieldsAndHelperDefinition($columnAttributeMapping);

            $fields = $exportService->getFieldsForExport();

            $query = $sfObject->recordsQuery($sObjectType , $fields);
            $records = $sfObject->queryAll($query);

            $classDefenction = DataObject\ClassDefinition::getById($pimcoreClassType);
            $pimcoreClassName = $classDefenction->getName();

            $exportService->setImportDataForPimcore($pimcoreClassName ,$parentFolderId, $fields, $mappingObject->getLanguage(), $records, $fieldforsfid,$output,false);
        }
        return Command::SUCCESS;
    }
}
