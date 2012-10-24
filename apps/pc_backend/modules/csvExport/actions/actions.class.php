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
  public function getEscapeString($haystack)
  {
    $tempStr = $haystack;
    $haystack = preg_replace('/\"/', '""', $haystack);
    if (is_null($haystack)) return $tempStr;
    return $haystack;
  }

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

      foreach (Doctrine::getTable('Profile')->retrievesAll() as $profile)
      {
        $profileIds[] = $profile->getId();
      }
      $memberLength = count($memberList);

      for ($i = 0; $i < $memberLength; $i++)
      {
        if (isset($memberList[$i]))
        {
          $member_id = $memberList[$i]['id'];

          $csvStr .= '"' . $member_id . '"';
          $csvStr .= ',"' . $this->getEscapeString($memberList[$i]['name']) . '"';
          $csvStr .= ',"' . $memberList[$i]['created_at'] . '"';
          $csvStr .= ',"' . $memberList[$i]['invite_member_id'] . '"';

          $tempConfig = array();
          $configInFlag = false;
          foreach ($configList as $config)
          {
            if ($configInFlag && $config['member_id'] != $member_id) break;
            if ($config['member_id'] == $member_id)
            {
              $configInFlag = true;
              switch ($config['name'])
              {
                case 'lastLogin':
                  $tempConfig['lastLogin'] = $config['value_datetime'];
                  break;

                case 'pc_address':
                  $tempConfig['pc_address'] = $config['value'];
                  break;

                case 'mobile_address':
                  $tempConfig['mobile_address'] = $config['value'];
                  break;
              }
            }
          }
          isset($tempConfig['lastLogin'])      ? $csvStr .= ',"' . $tempConfig['lastLogin'] . '"'      : $csvStr .= ',""';
          isset($tempConfig['pc_address'])     ? $csvStr .= ',"' . $tempConfig['pc_address'] . '"'     : $csvStr .= ',""';
          isset($tempConfig['mobile_address']) ? $csvStr .= ',"' . $tempConfig['mobile_address'] . '"' : $csvStr .= ',""';

          $tempFile =array();
          $imageInFlag = false;
          foreach ($imageList as $image)
          {
            if ($imageInFlag && $image['member_id'] != $member_id) break;
            if ($image['member_id'] == $member_id)
            {
              $imageInFlag = true;
              foreach ($fileList as $file)
              {
                if (count($tempFile) > 3) break;
                if ($image['file_id'] == $file['id'])
                {
                  $tempFile[] = $file['name'];
                }
              }
            }
          }

          for ($num = 0; $num < 3; $num++)
          {
            isset($tempFile[$num]) ? $csvStr .= ',"' . $tempFile[$num] . '"' : $csvStr .= ',""';
          }

          $tempProfile = array();
          $profileInFlag = false;
          foreach ($profileList as $profile)
          {
            if ($profileInFlag && $profile['member_id'] != $member_id) break;
            if ($profile['member_id'] == $member_id)
            {
              $profileInFlag = true;
              $tempProfile[$profile['profile_id'] - 1] = $profile['value'];
            }
          }

          foreach ($profileIds as $ids)
          {
            isset($tempProfile[$ids]) ? $csvStr .= ',"' . $this->getEscapeString($tempProfile[$ids]) . '"' : $csvStr .= ',""';
          }
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
