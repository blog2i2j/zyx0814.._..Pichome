<?php
    ignore_user_abort(true);
    @set_time_limit(0);
    global $_G;
    $recordid = isset($_GET['rdid']) ? intval($_GET['rdid']):0;
    if(!$recordid) exit(array('error'=>true));
    $drecoreddata = C::t('downfile_record')->fetch($recordid);
    $filedata = unserialize($drecoreddata['filedata']);
    //将文件复制到下载缓冲区
    $return = IO::moveFileToDownload($filedata['path'], $filedata['fpath']);
    if (!isset($return['error'])) {
        if($drecoreddata['path']) IO::delete($drecoreddata['path']);
        //更新下载记录表数据
        C::t('downfile_record')->update($recordid, array('status' => 4, 'path' => $filedata['path'], 'dateline' => time()));
        return $recordid;
    }