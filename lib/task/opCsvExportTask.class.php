<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */


/**
 * opCsvExportTask
 *
 * @package    opCsvExportPlugin
 * @subpackage task
 * @author     Yuya Watanabe <watanabe@tejimaya.com>
 */
class opCsvExportTask extends sfDoctrineBaseTask
{
  protected function configure()
  {
    parent::configure();
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),

      new sfCommandOption('from', null, sfCommandOption::PARAMETER_REQUIRED, 'member id which start from (inclusive)', 1),
      new sfCommandOption('to', null, sfCommandOption::PARAMETER_REQUIRED, 'member id which end to (exclusive)', null),

      new sfCommandOption('header', null, sfCommandOption::PARAMETER_OPTIONAL, 'need to output csv param header', true),
    ));

    $this->namespace        = 'opCsvExport';
    $this->name             = 'export';
  }

  protected function execute($arguments = array(), $options = array())
  {
    new sfDatabaseManager($this->configuration);

    if ('true' == $options['header'])
    {
      echo opMemberCsvList::getHeader()."\n";
    }

    $memberCsvList = new opMemberCsvList($options['from'], $options['to']);
    echo $memberCsvList->makeCsvList($memberCsvList->getFromTo($options['from'], $options['to']));
  }
}
