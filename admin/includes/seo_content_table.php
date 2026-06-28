<?php
/**
 * SEO Content Table Component
 * $all_items array'inin tanımlı olması gerekir
 */
if (!isset($all_items) || empty($all_items)):
    ?>
    <div class="text-center py-5">
        <i class="fas fa-inbox text-muted fa-4x mb-3"></i>
        <h5 class="text-muted">İçerik bulunamadı</h5>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table content-table">
            <thead>
                <tr>
                    <th width="40">
                        <input type="checkbox" class="item-checkbox form-check-input" id="selectAll">
                    </th>
                    <th width="50">Skor</th>
                    <th>İçerik</th>
                    <th width="200">Durum</th>
                    <th width="150">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_items as $item):
                    $analysis = $item['analysis'];
                    $score = $analysis['score'];
                    $score_class = $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
                    $type_badge = '';
                    $type_icon = '';
                    switch ($item['_type']) {
                        case 'blog':
                            $type_badge = 'bg-primary';
                            $type_icon = 'fas fa-blog';
                            break;
                        case 'page':
                            $type_badge = 'bg-success';
                            $type_icon = 'fas fa-file';
                            break;
                        case 'service':
                            $type_badge = 'bg-info';
                            $type_icon = 'fas fa-cogs';
                            break;
                    }
                    ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="item-checkbox form-check-input"
                                data-type="<?php echo $item['_type']; ?>" data-id="<?php echo $item['id']; ?>">
                        </td>
                        <td>
                            <span class="seo-score-badge badge bg-<?php echo $score_class; ?>">
                                <?php echo $score; ?>%
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-start">
                                <span class="badge <?php echo $type_badge; ?> me-2" style="font-size: 10px;">
                                    <i class="<?php echo $type_icon; ?>"></i>
                                </span>
                                <div>
                                    <strong class="d-block mb-1"><?php echo htmlspecialchars($item['_title']); ?></strong>
                                    <small class="text-muted">
                                        <?php if (!empty($item['slug'])): ?>
                                            <i class="fas fa-link me-1"></i>/<?php echo htmlspecialchars($item['slug']); ?>
                                        <?php else: ?>
                                            <span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>URL yok</span>
                                        <?php endif; ?>
                                    </small>

                                    <?php if (!empty($item['meta_description'])): ?>
                                        <div class="mt-1">
                                            <small class="text-muted"
                                                style="display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">
                                                <?php echo htmlspecialchars($item['meta_description']); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <?php
                                // Kritik sorunlar
                                foreach ($analysis['issues'] as $issue): ?>
                                    <span class="issue-badge critical" title="Kritik">
                                        <i class="fas fa-times-circle me-1"></i><?php echo htmlspecialchars($issue); ?>
                                    </span>
                                <?php endforeach; ?>

                                <?php
                                // Uyarılar (sadece ilk 2'si)
                                $warnings_shown = 0;
                                foreach ($analysis['warnings'] as $warning):
                                    if ($warnings_shown >= 2)
                                        break;
                                    $warnings_shown++;
                                    ?>
                                    <span class="issue-badge warning" title="Uyarı">
                                        <i class="fas fa-exclamation-triangle me-1"></i><?php echo htmlspecialchars($warning); ?>
                                    </span>
                                <?php endforeach; ?>

                                <?php if (count($analysis['warnings']) > 2):
                                    $hidden_warnings = array_slice($analysis['warnings'], 2);
                                    $all_warnings_text = implode("\n• ", $analysis['warnings']);
                                    ?>
                                    <span class="issue-badge warning" style="cursor:pointer;"
                                        onclick="showAllWarnings(this, '<?php echo htmlspecialchars($item['_title'], ENT_QUOTES); ?>')"
                                        data-warnings="<?php echo htmlspecialchars(json_encode($analysis['warnings']), ENT_QUOTES); ?>"
                                        title="Tıklayın: Tüm uyarıları görün">
                                        +<?php echo count($analysis['warnings']) - 2; ?> uyarı <i class="fas fa-eye ms-1"></i>
                                    </span>
                                <?php endif; ?>

                                <?php if (count($analysis['issues']) === 0 && count($analysis['warnings']) === 0): ?>
                                    <span class="issue-badge success">
                                        <i class="fas fa-check-circle me-1"></i>SEO Tamam
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-primary btn-sm"
                                    onclick="editItem('<?php echo $item['_type']; ?>', <?php echo $item['id']; ?>)"
                                    title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-success btn-sm"
                                    onclick="fixWithAiV2('<?php echo $item['_type']; ?>', <?php echo $item['id']; ?>, '<?php echo addslashes($item['_title']); ?>')"
                                    title="AI ile Düzelt">
                                    <i class="fas fa-magic"></i> AI
                                </button>
                                <?php if (!empty($item['slug'])): ?>
                                    <a href="<?php echo SITE_URL; ?>/<?php echo htmlspecialchars($item['slug']); ?>" target="_blank"
                                        class="btn btn-secondary btn-sm" title="Görüntüle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>