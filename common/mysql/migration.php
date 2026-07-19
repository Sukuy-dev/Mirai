<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_checkDatabase() {
    Mirai_ensureDatabaseSchema();
}

function Mirai_getSchemaDefinition() {
    return [
        'mirai_crawler' => [
            'columns' => [
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'name' => 'varchar(255) NOT NULL DEFAULT \'\' COMMENT \'爬虫名称\'',
                'ua' => 'varchar(500) NOT NULL DEFAULT \'\' COMMENT \'User-Agent\'',
                'url' => 'varchar(500) NOT NULL DEFAULT \'\' COMMENT \'访问URL\'',
                'ip_address' => 'varchar(50) DEFAULT NULL COMMENT \'访问IP地址\'',
                'crawled_at' => 'int(10) unsigned DEFAULT \'0\' COMMENT \'访问时间戳\'',
            ],
            'indexes' => [
                'PRIMARY' => ['id', 'primary' => true],
                'name' => ['name'],
                'crawled_at' => ['crawled_at'],
                'ip_address' => ['ip_address'],
            ],
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'comment' => 'Mirai爬虫访问记录表',
        ],
        'contents' => [
            'columns' => [
                'likes' => 'INT(10) DEFAULT 0',
                'cover' => 'VARCHAR(255) DEFAULT NULL',
                'views' => 'INT(10) UNSIGNED DEFAULT 0',
            ],
            'indexes' => [
                'views' => ['views'],
            ],
        ],
        'comments' => [
            'columns' => [
                'ip_location' => 'VARCHAR(64) DEFAULT NULL COMMENT "IP归属地"',
                'ip_raw' => 'VARCHAR(64) DEFAULT NULL COMMENT "接口IP(REMOTE_ADDR)"',
                'ip_resolved' => 'VARCHAR(64) DEFAULT NULL COMMENT "函数解析IP(代理头)"',
                'ip_info' => 'TEXT DEFAULT NULL COMMENT "IP详细信息(JSON)"',
            ],
            'indexes' => [],
        ],
        'mirai_actions' => [
            'columns' => [
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'gid' => 'int(10) unsigned DEFAULT \'0\'',
                'uid' => 'int(10) unsigned DEFAULT \'0\'',
                'type' => 'varchar(16) DEFAULT NULL',
                'created' => 'int(10) unsigned DEFAULT \'0\'',
            ],
            'indexes' => [
                'PRIMARY' => ['id', 'primary' => true],
                'gid' => ['gid'],
                'uid' => ['uid'],
                'type' => ['type'],
                'gid_type' => ['gid', 'type'],
                'uid_type' => ['uid', 'type'],
            ],
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
        ],
        'mirai_contents_edk' => [
            'columns' => [
                'cid' => 'int(10) unsigned NOT NULL',
                'excerpt' => 'text DEFAULT NULL COMMENT "文章摘要"',
                'keywords' => 'varchar(255) DEFAULT NULL COMMENT "文章关键词"',
                'description' => 'text DEFAULT NULL COMMENT "文章描述"',
            ],
            'indexes' => [
                'PRIMARY' => ['cid', 'primary' => true],
                'cid_idx' => ['cid'],
            ],
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'comment' => '文章EDK扩展表（摘要、关键词、描述）',
        ],
        'mirai_auth' => [
            'columns' => [
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'license' => 'varchar(255) NOT NULL DEFAULT \'\' COMMENT "授权码"',
                'created' => 'int(10) unsigned DEFAULT \'0\' COMMENT "创建时间"',
                'updated' => 'int(10) unsigned DEFAULT \'0\' COMMENT "更新时间"',
            ],
            'indexes' => [
                'PRIMARY' => ['id', 'primary' => true],
            ],
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'comment' => 'Mirai主题授权表',
        ],
        'mirai_pay_orders' => [
            'columns' => [
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'order_no' => 'varchar(32) NOT NULL',
                'uid' => 'int(10) unsigned DEFAULT \'0\'',
                'cid' => 'int(10) unsigned DEFAULT \'0\'',
                'author_id' => 'int(10) unsigned DEFAULT \'0\'',
                'order_type' => 'varchar(20) NOT NULL DEFAULT \'read\'',
                'product_title' => 'varchar(255) DEFAULT \'\' COMMENT \'商品名称\'',
                'payment_method' => 'varchar(20) NOT NULL DEFAULT \'balance\'',
                'amount' => 'decimal(10,2) NOT NULL DEFAULT \'0.00\'',
                'income_price' => 'decimal(10,2) NOT NULL DEFAULT \'0.00\' COMMENT \'作者分成金额\'',
                'income_status' => 'tinyint(1) NOT NULL DEFAULT 0 COMMENT \'分成状态:0未提现,1已提现,2待处理\'',
                'income_detail' => 'text DEFAULT NULL COMMENT \'分成详情JSON\'',
                'status' => 'varchar(20) NOT NULL DEFAULT \'pending\'',
                'trade_no' => 'varchar(64) DEFAULT \'\'',
                'ip_address' => 'varchar(50) DEFAULT \'\' COMMENT \'IP地址\'',
                'guest_token' => 'varchar(64) DEFAULT \'\'',
                'query_token' => 'varchar(64) DEFAULT \'\'',
                'meta' => 'text DEFAULT NULL',
                'created' => 'int(10) unsigned DEFAULT \'0\'',
                'paid_at' => 'int(10) unsigned DEFAULT \'0\'',
            ],
            'indexes' => [
                'PRIMARY' => ['id', 'primary' => true],
                'order_no' => ['order_no', 'unique' => true],
                'uid' => ['uid'],
                'cid' => ['cid'],
                'status' => ['status'],
                'author_status_income' => ['author_id', 'status', 'income_status'],
                'status_created' => ['status', 'created'],
                'uid_status' => ['uid', 'status'],
            ],
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
        ],
        'mirai_pay_wallets' => [
            'columns' => [
                'uid' => 'int(10) unsigned NOT NULL',
                'balance' => 'decimal(10,2) NOT NULL DEFAULT \'0.00\'',
                'updated' => 'int(10) unsigned DEFAULT \'0\'',
            ],
            'indexes' => [
                'PRIMARY' => ['uid', 'primary' => true],
            ],
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
        ],
        'mirai_pay_wallet_logs' => [
            'columns' => [
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'uid' => 'int(10) unsigned NOT NULL',
                'type' => 'varchar(20) NOT NULL DEFAULT \'\'',
                'amount' => 'decimal(10,2) NOT NULL DEFAULT \'0.00\'',
                'balance_before' => 'decimal(10,2) NOT NULL DEFAULT \'0.00\'',
                'balance_after' => 'decimal(10,2) NOT NULL DEFAULT \'0.00\'',
                'remark' => 'varchar(255) DEFAULT \'\'',
                'order_no' => 'varchar(32) DEFAULT \'\'',
                'created' => 'int(10) unsigned DEFAULT \'0\'',
            ],
            'indexes' => [
                'PRIMARY' => ['id', 'primary' => true],
                'uid' => ['uid'],
                'order_no' => ['order_no'],
            ],
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
        ],
        'mirai_pay_withdrawals' => [
            'columns' => [
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'uid' => 'int(10) unsigned NOT NULL COMMENT \'用户ID\'',
                'amount' => 'decimal(10,2) NOT NULL DEFAULT \'0.00\' COMMENT \'提现金额\'',
                'withdraw_type' => 'varchar(20) NOT NULL DEFAULT \'balance\' COMMENT \'提现类型:balance余额提现,income收益提现\'',
                'status' => 'tinyint(1) NOT NULL DEFAULT 0 COMMENT \'状态:0待处理,1已通过,2已拒绝,3已取消\'',
                'account_type' => 'varchar(20) NOT NULL DEFAULT \'\' COMMENT \'账户类型:alipay/wechat/bank/alipay_qr/wechat_qr\'',
                'account_name' => 'varchar(100) NOT NULL DEFAULT \'\' COMMENT \'账户名称\'',
                'account_no' => 'varchar(100) NOT NULL DEFAULT \'\' COMMENT \'账户号码\'',
                'qr_code' => 'varchar(255) DEFAULT \'\' COMMENT \'收款二维码图片URL\'',
                'remark' => 'varchar(255) DEFAULT \'\' COMMENT \'备注\'',
                'admin_remark' => 'varchar(255) DEFAULT \'\' COMMENT \'管理员备注\'',
                'created' => 'int(10) unsigned DEFAULT \'0\' COMMENT \'申请时间\'',
                'processed_at' => 'int(10) unsigned DEFAULT \'0\' COMMENT \'处理时间\'',
            ],
            'indexes' => [
                'PRIMARY' => ['id', 'primary' => true],
                'status' => ['status'],
                'uid_status' => ['uid', 'status'],
                'withdraw_type' => ['withdraw_type'],
            ],
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'comment' => '提现申请表',
        ],
        'users' => [
            'columns' => [
                'avatar' => 'VARCHAR(255) DEFAULT NULL COMMENT \'用户头像URL\'',
                'motto' => 'VARCHAR(255) DEFAULT NULL COMMENT \'个人简介\'',
                'cover' => 'VARCHAR(255) DEFAULT NULL COMMENT \'主页背景图URL\'',
                'vip_level' => 'INT(10) DEFAULT 0 COMMENT \'会员等级\'',
                'vip_exp_date' => 'VARCHAR(50) DEFAULT NULL COMMENT \'会员到期时间\'',
            ],
            'indexes' => [],
        ],
        'mirai_links' => [
            'columns' => [
                'lid' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'name' => 'varchar(255) NOT NULL DEFAULT \'\' COMMENT \'网站名称\'',
                'url' => 'varchar(500) NOT NULL DEFAULT \'\' COMMENT \'网站地址\'',
                'image' => 'varchar(500) DEFAULT NULL COMMENT \'网站LOGO\'',
                'description' => 'varchar(500) DEFAULT NULL COMMENT \'网站描述\'',
                'category' => 'int(10) unsigned DEFAULT \'0\' COMMENT \'分类ID\'',
                'sort' => 'int(10) unsigned DEFAULT \'0\' COMMENT \'排序权重\'',
                'visible' => 'char(1) DEFAULT \'N\' COMMENT \'是否可见(Y/N)\'',
                'created' => 'int(10) unsigned DEFAULT \'0\' COMMENT \'创建时间\'',
                'updated' => 'int(10) unsigned DEFAULT \'0\' COMMENT \'更新时间\'',
            ],
            'indexes' => [
                'PRIMARY' => ['lid', 'primary' => true],
                'category' => ['category'],
                'visible' => ['visible'],
                'sort' => ['sort'],
            ],
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'comment' => '友情链接表',
        ],
    ];
}

function Mirai_ensureDatabaseSchema() {
    $db = \Typecho\Db::get();
    $prefix = $db->getPrefix();
    $schema = Mirai_getSchemaDefinition();

    foreach ($schema as $tableName => $tableDef) {
        $fullTableName = $prefix . $tableName;
        
        try {
            $tableExists = $db->fetchRow($db->query("SHOW TABLES LIKE '{$fullTableName}'"));

            if (!$tableExists) {
                // Create new table
                if (!isset($tableDef['engine'])) continue; // Skip altering existing tables like 'contents'

                $sql = "CREATE TABLE `{$fullTableName}` (\n";
                $columnDefs = [];
                foreach ($tableDef['columns'] as $colName => $colDef) {
                    $columnDefs[] = "  `{$colName}` {$colDef}";
                }
                $sql .= implode(",\n", $columnDefs);

                if (!empty($tableDef['indexes'])) {
                    $indexDefs = [];
                    foreach ($tableDef['indexes'] as $indexName => $indexDef) {
                        $isPrimary = isset($indexDef['primary']) && $indexDef['primary'];
                        $isUnique = isset($indexDef['unique']) && $indexDef['unique'];
                        $cols = is_array($indexDef) ? $indexDef : [$indexDef];
                        $colNames = [];
                        foreach ($cols as $key => $val) {
                            if (is_string($key) && in_array($key, ['primary', 'unique'], true)) {
                                continue;
                            }
                            $colNames[] = '`' . (is_string($val) ? $val : (is_int($key) ? $val : $key)) . '`';
                        }
                        if (empty($colNames)) continue;
                        $colList = implode(', ', $colNames);
                        if ($isPrimary) {
                            $indexDefs[] = "  PRIMARY KEY ({$colList})";
                        } else if ($isUnique) {
                            $indexDefs[] = "  UNIQUE KEY `{$indexName}` ({$colList})";
                        } else {
                            $indexDefs[] = "  KEY `{$indexName}` ({$colList})";
                        }
                    }
                    $sql .= ",\n" . implode(",\n", $indexDefs);
                }
                
                $sql .= "\n) ENGINE=" . ($tableDef['engine'] ?? 'InnoDB') . 
                        " DEFAULT CHARSET=" . ($tableDef['charset'] ?? 'utf8mb4') . 
                        (isset($tableDef['comment']) ? " COMMENT='" . addslashes($tableDef['comment']) . "'" : "") . ";";
                
                $db->query($sql);

            } else {
                // Check existing table for columns and indexes
                $existingColumnsResult = $db->fetchAll($db->query("SHOW COLUMNS FROM `{$fullTableName}`"));
                $existingColumns = [];
                foreach ($existingColumnsResult as $c) {
                    $existingColumns[$c['Field']] = true;
                }

                foreach ($tableDef['columns'] as $colName => $colDef) {
                    if (!isset($existingColumns[$colName])) {
                        $db->query("ALTER TABLE `{$fullTableName}` ADD COLUMN `{$colName}` {$colDef}");
                    }
                }

                if (!empty($tableDef['indexes'])) {
                    $existingIndexesResult = $db->fetchAll($db->query("SHOW INDEX FROM `{$fullTableName}`"));
                    $existingIndexes = [];
                    foreach ($existingIndexesResult as $i) {
                        $existingIndexes[$i['Key_name']] = true;
                    }

                    foreach ($tableDef['indexes'] as $indexName => $indexDef) {
                        if (!isset($existingIndexes[$indexName])) {
                            $isPrimary = isset($indexDef['primary']) && $indexDef['primary'];
                            if ($isPrimary) continue;

                            $isUnique = isset($indexDef['unique']) && $indexDef['unique'];
                            $cols = is_array($indexDef) ? $indexDef : [$indexDef];
                            $colNames = [];
                            foreach ($cols as $key => $val) {
                                if (is_string($key) && in_array($key, ['primary', 'unique'], true)) {
                                    continue;
                                }
                                $colNames[] = '`' . (is_string($val) ? $val : (is_int($key) ? $val : $key)) . '`';
                            }
                            if (empty($colNames)) continue;
                            $colList = implode(', ', $colNames);
                            $indexType = $isUnique ? 'UNIQUE INDEX' : 'INDEX';
                            $db->query("ALTER TABLE `{$fullTableName}` ADD {$indexType} `{$indexName}` ({$colList})");
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // You might want to log this error instead of throwing it, to allow other tables to be processed.
            error_log("Mirai migration error for table {$fullTableName}: " . $e->getMessage());
        }
    }
}
