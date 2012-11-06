<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */


/**
 * opMemberCsvList
 *
 * @package    opCsvExportPlugin
 * @subpackage model
 * @author     Yuya Watanabe <watanabe@tejimaya.com>
 */
class opMemberCsvList implements Iterator
{
  private $memberIds;
  private $position = 0;

  public function __construct($from, $to)
  {
    $query = Doctrine::getTable('Member')->createQuery()->select('id')->where('? <= id', $from);
    if (!is_null($to))
    {
      $query = $query->andWhere('id <= ?', $to);
    }

    $this->memberIds = $query->execute(array(), Doctrine::HYDRATE_NONE);
  }

  public function rewind()
  {
    $this->position = 0;
  }

  public function current()
  {
    return $this->getMemberCsv($this->memberIds[$this->position]);
  }

  public function key()
  {
    return $this->memberIds[$this->position];
  }

  public function next()
  {
    ++$this->position;
  }

  public function valid()
  {
    return 0 <= $this->position && $this->position < count($this->memberIds);
  }

  public function getMemberIds()
  {
    return $this->memberIds;
  }

  public function getMemberCsv($memberId)
  {
    $line = array();

    $member = Doctrine::getTable('Member')->find($memberId);
    $line[] = $this->getString($member->getId());
    $line[] = $this->getString($member->getName());
    $line[] = $this->getString($member->getCreatedAt());
    $line[] = $this->getString($member->getInviteMemberId());
    $line[] = $this->getString($member->getConfig('lastLogin'));
    $line[] = $this->getString($member->getConfig('pc_address'));
    $line[] = $this->getString($member->getConfig('mobile_address'));
    $memberImages = $member->getMemberImage();
    for ($i = 0; $i < 3; ++$i)
    {
      $line[] = $this->getString($memberImages[$i]->getFile());
    }
    foreach (Doctrine::getTable('Profile')->retrievesAll() as $profile)
    {
      $line[] = $this->getString($member->getProfile($profile->getName()));
    }

    return '"'.implode('","', $line).'"';
  }

  private function getString($str)
  {
    return is_null($str) || false === $str ? '' : $str;
  }

  static public function getHeader()
  {
    $result = array('id', 'name', 'created_at', 'invite_member_id', 'lastLogin', 'pc_address', 'mobile_address');

    for ($i = 1; $i <= 3; ++$i)
    {
      $result[] = 'memberImage'.$i;
    }

    foreach (Doctrine::getTable('Profile')->retrievesAll() as $profile)
    {
      $result[] = $profile->getName();
    }

    return '"'.implode('","', $result).'"';
  }

  public function getFromTo($from, $to)
  {
    $con = Doctrine_Manager::connection();
    $memberQuery  = Doctrine::getTable('Member')->createQuery()->select('id, name, created_at, invite_member_id')->where('? <= id', $from);
    $configQuery  = Doctrine::getTable('MemberConfig')->createQuery()->select('member_id, name, value, value_datetime')->where('? <= member_id', $from);
    $profileQuery = Doctrine::getTable('MemberProfile')->createQuery()->select('member_id, profile_id, profile_option_id, value')->where('? <= member_id', $from)->orderBy('member_id');
    $imageQuery   = Doctrine::getTable('MemberImage')->createQuery()->select('member_id, file_id')->where('? <= member_id', $from)->orderBy('member_id');
    $fileQuery    = Doctrine::getTable('File')->createQuery()->select('id, name')->where('id = any (select file_id from member_image where ? <= member_id)', $from);
    $profileOptionTranslationQuery = $con->fetchAll('select id,value from profile_option_translation where lang = "en"', array(), Doctrine::HYDRATE_ARRAY);
    if (!is_null($to))
    {
      $memberQuery  = $memberQuery->andWhere('id <= ?', $to);
      $configQuery  = $configQuery->andWhere('member_id <= ?', $to);
      $profileQuery = $profileQuery->andWhere('member_id <= ?', $to);
      $imageQuery   = $imageQuery->andWhere('member_id <= ?', $to);
      $fileQuery    = Doctrine::getTable('File')->createQuery()->select('id, name')->where('id = any (select file_id from member_image where ? <= member_id and member_id <= ?)',array($from ,$to));
    }

    return array('memberList' => $memberQuery->execute(array(), Doctrine::HYDRATE_ARRAY),
                 'configList' => $configQuery->execute(array(), Doctrine::HYDRATE_ARRAY),
                 'profileList' => $profileQuery->execute(array(), Doctrine::HYDRATE_ARRAY),
                 'imageList' => $imageQuery->execute(array(), Doctrine::HYDRATE_ARRAY),
                 'fileList' => $fileQuery->execute(array(), Doctrine::HYDRATE_ARRAY),
                 'optionTranslationList' => $profileOptionTranslationQuery);
  }

  public function getEscapeString($haystack)
  {
    $tempStr = $haystack;
    $haystack = preg_replace('/\"/', '""', $haystack);
    if (is_null($haystack)) return $tempStr;
    return $haystack;
  }

  public function makeCsvList($dataList)
  {
    $memberList = $dataList['memberList'];
    $configList = $dataList['configList'];
    $profileList = $dataList['profileList'];
    $imageList = $dataList['imageList'];
    $fileList = $dataList['fileList'];
    $optionTranslationList = $dataList['optionTranslationList'];
    $csvStr = $this->getHeader() . "\n";

    foreach (Doctrine::getTable('Profile')->retrievesAll() as $profile)
    {
      $profileItems[$profile->getId()] = $profile;
    }

    foreach ($optionTranslationList as $option)
    {
      $optionList[$option['id']] = $option;
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

        $tempProfile = array_fill(0, count($profileList), '');
        $profileInFlag = false;
        foreach ($profileList as $profile)
        {
          if ($profileInFlag && $profile['member_id'] != $member_id) break;
          if ($profile['member_id'] == $member_id)
          {
            $profileInFlag = true;
            switch ($profileItems[$profile['profile_id']]['form_type'])
            {
            case 'date':
              if ('' === $tempProfile[$profile['profile_id']])
              {
                $tempProfile[$profile['profile_id']] = $profile['value'];
              }
              else
              {
                $tempProfile[$profile['profile_id']] .= '-' . $profile['value'];
              }
              break;
            case 'radio': case 'select': case 'checkbox':
              if (is_null($profile['profile_option_id']) && is_null($profile['value']))
              {
                $tempProfile[$profile['profile_id']] = '';
              }
              elseif (is_null($profile['profile_option_id']) && '' !== $profile['value'])
              {
                $tempProfile[$profile['profile_id']] = $profile['value'];
              }
              elseif (!is_null($profile['profile_option_id']))
              {
                $tempProfile[$profile['profile_id']] .= $optionList[$profile['profile_option_id']]['value'];
              }
              break;
            default:
              $tempProfile[$profile['profile_id']] = $profile['value'];
              break;
            }
          }
        }

        foreach ($profileItems as $items)
        {
          isset($tempProfile[$items['id']]) ? $csvStr .= ',"' . $this->getEscapeString($tempProfile[$items['id']]) . '"' : $csvStr .= ',""';
        }
        $csvStr .= "\n";
      }
    }
    return $csvStr;
  }
}
