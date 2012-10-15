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

      $dataList = opMemberCsvList::getFromTo($this->form->getValue('from'), $this->form->getValue('to'));
      $memberList = $dataList['memberList'];
      $configList = $dataList['configList'];
      $profileList = $dataList['profileList'];
      $imageList = $dataList['imageList'];
      $fileList = $dataList['fileList'];
      $csvStr = opMemberCsvList::getHeader()."\n";

      $memberLength = count($memberList);
      for ($i = 0; $i < $memberLength; $i++)
      {
        if (isset($memberList[$i]))
        {
          $member_id = $memberList[$i]['id'];

          $csvStr .= '"'.implode('","', $memberList[$i]).'"';

          $tmpconf = array('', '', '');
          foreach ($configList as $config)
          {
            if ($config['member_id'] == $member_id)
            {
              switch ($config['name'])
              {
                case 'lastLogin':
                  $tmpconf[0] = $config['value_datetime'];
                  break;

                case 'pc_address':
                  $tmpconf[1] = $config['value'];
                  break;

                case 'mobile_address':
                  $tmpconf[2] = $config['value'];
                  break;
              }
            }
          }
          $csvStr .= ',"'.implode('","', $tmpconf).'"';

          $tmpfile =array('', '', '');
          $num = 0;
          foreach ($imageList as $image)
          {
            if ($image['member_id'] == $member_id)
            {
              foreach ($fileList as $file)
              {
                if ($image['file_id'] == $file['id'])
                {
                  $tmpfile[$num] = $file['name'];
                  $num++;
                }
              }
            }
          }

          $csvStr .= ',"'.implode('","', $tmpfile).'"';

          $tmpprof = array('', '', '', '', '');
          foreach ($profileList as $profile)
          {
            if($profile['member_id'] == $member_id)
            {
              $tmpprof[$profile['profile_id'] - 1] = $profile['value'];
            }
          }
          $csvStr .= ',"'.implode('","', $tmpprof).'"';
          $csvStr .= "\n";
        }
      }

      if( 'UTF-8' != $this->form->getValue('encode'))
      {
        $csvStr = mb_convert_encoding($csvStr, $this->form->getValue('encode'), 'UTF-8');
      }
      opToolkit::fileDownload('member.csv', $csvStr);

      return sfView::NONE;
    }
  }
}
