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

      for ($i = 0; $i <= count($memberList) ; $i++)
      {
        if(isset($memberList[$i]))
        {
          $csvStr .= '"'.implode('","', $memberList[$i]).'"';

          $tmpconf[0] = '';
          $tmpconf[1] = '';
          $tmpconf[2] = '';
          foreach ($configList as $config)
          {
            if ($config[0] == ($i + 1))
            {
              switch ($config[1])
              {
                case 'lastLogin':
                  $tmpconf[0] = $config[3];
                  break;

                case 'pc_address':
                  $tmpconf[1] = $config[2];
                  break;

                case 'mobile_address':
                  $tmpconf[2] = $config[2];
                  break;
              }
            }
          }

          $csvStr .= ',"'.$tmpconf[0].'","'.$tmpconf[1].'","'.$tmpconf[2].'"';
          $tmpfile = array();
          foreach ($imageList as $image)
          {
            if ($image[0] == ($i + 1))
            {
              foreach ($fileList as $file)
              {
                if ($image[1] == $file[0])
                {
                  $tmpfile[] = $file[1];
                }
              }
            }
          }
          if (count($tmpfile))
          {
            $csvStr .= ',"'.implode('","', $tmpfile).'"';
          }
          else
          {
            $csvStr .= ',"","",""';
          }

          $tmpprof[0] = '';
          $tmpprof[1] = '';
          $tmpprof[2] = '';
          $tmpprof[3] = '';
          $tmpprof[4] = '';
          foreach ($profileList as $profile)
          {
            if($profile[0] == ($i + 1))
            {
              switch ($profile[1])
              {
                case 1:
                  $tmpprof[0] = $profile[2];
                  break;

                case 2:
                  $tmpprof[1] = $profile[2];
                  break;

                case 3:
                  $tmpprof[2] = $profile[2];
                  break;

                case 4:
                  $tmpprof[3] = $profile[2];
                  break;

                case 5:
                  $tmpprof[4] = $profile[2];
                  break;
              }
            }
          }
          $csvStr .= ',"'.$tmpprof[0].'","'
                        .$tmpprof[1].'","'
                        .$tmpprof[2].'","'
                        .$tmpprof[3].'","'
                        .$tmpprof[4].'"';
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

  private function getString($str)
  {
    return is_null($str) || false === $str ? '' : $str;
  }
}
