<?php
namespace Vmasciotta\ProvinceItaliane\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;


class UpgradeData implements UpgradeDataInterface
{
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $countryId = 'IT';
            $provinceToBeRemoved = "'CI', 'OT', 'VS', 'OG'";
            $provinceToBeAdded = ['SU' => 'Sud Sardegna'];

            $connection = $setup->getConnection();

            $select = $connection->select();
            $select
                ->from('directory_country_region')
                ->where(sprintf("code IN (%s) and country_id = '%s'", $provinceToBeRemoved, $countryId));
            $regionsToBeDeleted = $connection->query($select)->fetchAll();
            foreach ($regionsToBeDeleted as $region) {
                $regionId = $region['region_id'];
                $connection->delete('directory_country_region_name', 'region_id = ' . $regionId);
                $connection->delete('directory_country_region', 'region_id = ' . $regionId);
            }

            foreach ($provinceToBeAdded as $code => $name) {
                $bind = ['country_id'   => $countryId, 'code' => $code, 'default_name' => $name];
                $setup->getConnection()->insert($setup->getTable('directory_country_region'), $bind);
                $regionId = $setup->getConnection()->lastInsertId($setup->getTable('directory_country_region'));


                $bind = ['locale'=> 'it_IT', 'region_id' => $regionId, 'name'=> $name];
                $connection->insert($setup->getTable('directory_country_region_name'), $bind);
            }

        }

        $setup->endSetup();
    }
}