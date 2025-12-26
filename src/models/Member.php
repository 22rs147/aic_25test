<?php

namespace aic\models;

class Member extends Model
{
    protected $table = "tb_member";

    public function getDetailByUid($uid)
    {
        $uid_escaped = $this->db->real_escape_string($uid);
        $sql = sprintf("SELECT * FROM %s WHERE uid='%s'", $this->table, $uid_escaped);
        $rs = $this->db->query($sql);
        if (!$rs) die('エラー: ' . $this->db->error);
        return $rs->fetch_assoc();
    }

    public function getDetailBySid($sid)
    {
        $member = $this->getList("sid='$sid'");
        if (count($member) > 0) {
            return $member[0];
        }
        return null;
    }
}
