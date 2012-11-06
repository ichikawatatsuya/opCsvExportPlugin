<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * csvExportActions
 *
 * @package    opCsvExportPlugin
 * @author     Yuya Watanabe <watanabe@tejimaya.com>
 */
class csvExportActions extends sfActions
{
  public function executeDownload(sfWebRequest $request)
  {
    $this->form = new opCsvExportForm();

    if ($request->isMethod(sfRequest::POST))
    {
      $this->form->bind($request->getParameter('opCsvExport'));

      if (!$this->form->isValid())
      {
        return sfView::SUCCESS;
      }

      $instance = new opMemberCsvList($this->form->getValue('from'), $this->form->getValue('to'));
      $csvStr = $instance->makeCsvList($instance->getFromTo($this->form->getValue('from'), $this->form->getValue('to')));

      if( 'UTF-8' != $this->form->getValue('encode'))
      {
        $csvStr = mb_convert_encoding($csvStr, $this->form->getValue('encode'), 'UTF-8');
      }
      opToolkit::fileDownload('member.csv', $csvStr);

      return sfView::NONE;
    }
  }
}
