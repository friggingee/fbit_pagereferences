CREATE TABLE pages
(
    tx_fbit_pagereferences_reference_source_page int(11) unsigned DEFAULT '0' NOT NULL,
    tx_fbit_pagereferences_reference_page_properties tinyint(4) unsigned DEFAULT '0' NOT NULL,
    tx_fbit_pagereferences_original_page_properties TEXT,
    tx_fbit_pagereferences_rewrite_links tinyint(4) unsigned DEFAULT '0' NOT NULL,
);

