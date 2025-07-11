<?php
namespace aic\models;

class Member extends Model 
{
    protected $table = "tb_member";

    /**
     * ユーザーID(uid)をキーに会員詳細情報を取得する
     * 
     * @param string $uid ユーザーID
     * @return array|null 会員情報。見つからない場合はnull
     */
    public function getDetailByUid($uid)
    {
        $uid_escaped = $this->db->real_escape_string($uid);
        $sql = sprintf("SELECT * FROM %s WHERE uid='%s'", $this->table, $uid_escaped);
        $rs = $this->db->query($sql);
        if (!$rs) die('エラー: ' . $this->db->error);
        return $rs->fetch_assoc(); 
    }
}