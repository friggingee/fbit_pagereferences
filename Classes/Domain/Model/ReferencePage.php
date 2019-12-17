<?php

namespace FBIT\PageReferences\Domain\Model;

class ReferencePage
{
    const DOKTYPE = 111;

    const PROTECTED_PROPERTIES = [
        'uid',
        'pid',
        't3ver_oid',
        't3ver_id',
        't3ver_wsid',
        't3ver_label',
        't3ver_state',
        't3ver_stage',
        't3ver_count',
        't3ver_tstamp',
        't3ver_move_id',
        't3_origuid',
        'tstamp',
        'sorting',
        'deleted',
        'perms_userid',
        'perms_groupid',
        'perms_user',
        'perms_group',
        'perms_everybody',
        'editlock',
        'crdate',
        'cruser_id',
        'starttime',
        'endtime',
        'fe_group',
        'l10n_parent',
        'l10n_source',
        'l10n_state',
        'l10n_diffsource',
        'legacy_overlay_uid',
        'doktype',
        'content_from_pid',
        'slug',
        'hidden',
        'tx_fbit_pagereferences_rewrite_links',
        'tx_fbit_pagereferences_reference_page_properties',
        'tx_fbit_pagereferences_original_page_properties',
        'SYS_LASTCHANGED',
    ];
}
