<?php require 'apifetch.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event List</title>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
</head>
<body>
    <div class="main-content" style="display: flex; justify-content: center; overflow-y: hidden;">
        <div class="content-card">
            <div class="card-title-row">
                <h2 class="coolveticaa">Events</h2>
                <button class="btn-add-green" onclick="openAddEventModal()">+ Add Event</button>
            </div>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Event Name</th>
                        <th>Event Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Discount</th>
                        <th style="max-width: 80px;">Featured Product</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($stmt_event)): ?>
                        <?php
                            $limit = 7;
                            $no = (($page - 1) * $limit) + 1;
                        ?>
                        <?php foreach ($stmt_event as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>

                                <td style="font-weight: 600; text-align: center;">
                                    <?= htmlspecialchars($row['nama_event'] ?? '-') ?>
                                </td>

                                <td><?= htmlspecialchars($row['tipe_event'] ?? '-') ?></td>

                                <td>
                                    <?= isset($row['tanggal_mulai']) && $row['tanggal_mulai'] instanceof DateTime
                                        ? $row['tanggal_mulai']->format('d-m-Y')
                                        : '-' ?>
                                </td>

                                <td>
                                    <?= isset($row['tanggal_berakhir']) && $row['tanggal_berakhir'] instanceof DateTime
                                        ? $row['tanggal_berakhir']->format('d-m-Y')
                                        : '-' ?>
                                </td>

                                <td style="font-weight: bold; text-align: right;">
                                    <?= number_format((float)($row['persen_diskon'] ?? 0), 0, ',', '.') ?>%
                                </td>

                                <td style="text-align: right;">
                                    <?= (int)($row['total_item'] ?? 0) ?>
                                </td>

                                <td>
                                    <?php 
                                    $status = $row['status_event'] ?? 0; // Ambil status utama
                                    $is_hide = $row['is_hide'] ?? 0;     // Ambil status hide

                                    // Siapkan teks tambahan jika event sedang disembunyikan
                                    $hideBadge = ($is_hide == 1) ? ' <span style="color: #7F8C8D; font-weight: normal; font-size: 0.9em;">(Hidden)</span>' : '';

                                    if ($status == 1): ?>
                                        <span style="color: #27AE60; font-weight: bold;">Running</span><?= $hideBadge ?>

                                    <?php elseif ($status == 2): ?>
                                        <span style="color: #F39C12; font-weight: bold;">Upcoming</span><?= $hideBadge ?>

                                    <?php else: ?>
                                        <span style="color: var(--primary-color); font-weight: bold;">Complete</span><?= $hideBadge ?>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="btn-action-group">
                                        <button class="btn-view-icon"
                                            onclick="openEventModal(<?= (int)$row['id_event'] ?>)">
                                            ...
                                        </button>

                                        <?php if($row['status_event'] == 1 || $row['status_event'] == 2): ?>
                                        <button class="btn-edit-icon"
                                            onclick="openEditModal(<?= (int)$row['id_event'] ?>)">
                                            <img src="/cardhaven/assets/image/edit.svg" alt="">
                                        </button>
                                        <?php elseif($row['status_event'] == 0): ?>
                                            <button class="btn-complete-icon"
                                                style=" cursor: default;">
                                                <img src="/cardhaven/assets/image/edit.svg" alt="">
                                            </button>
                                        <?php endif; ?>

                                        <?php if($row['status_event'] == 1): ?>
                                        <button class="btn-delete-icon"
                                            onclick="completeEvent(<?= (int)$row['id_event'] ?>)">
                                            <img src="/cardhaven/assets/image/clock-arrow-down.svg" alt="">
                                        </button>
                                        <?php elseif($row['status_event'] == 0): ?>
                                            <button class="btn-complete-icon"
                                                style=" cursor: default;">
                                                <img src="/cardhaven/assets/image/clock-check.svg" alt="">
                                            </button>
                                        <?php elseif($row['status_event'] == 2): ?>
                                            <button class="btn-edit-icon"
                                                onclick="moveUp(<?= (int)$row['id_event'] ?>)">
                                                <img src="/cardhaven/assets/image/clock-arrow-up.svg" alt="">
                                            </button>
                                        <?php endif; ?>

                                        <button class="btn-delete-icon"
                                            onclick="deleteEvent(<?= (int)$row['id_event'] ?>)">
                                            <img src="/cardhaven/assets/image/delete.svg" alt="">
                                        </button>

                                        <label class="switch" title="Hide Event from Customers">
                                            <input type="checkbox" 
                                                <?= (int)($row['is_hide'] ?? 0) === 0 ? 'checked="checked"' : '' ?> 
                                                onchange="hideEvent(<?= (int)$row['id_event'] ?>, this.checked, this)">
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">No events found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="pagination-container">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="page-link">&lt;</a>
                <?php else: ?>
                    <span class="page-link disabled">&lt;</span>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 1);
                $end   = min($total_pages, $page + 1);

                if ($start > 1):
                ?>
                    <a href="?page=1" class="page-link <?= $page == 1 ? 'active' : '' ?>">1</a>
                    <?php if ($start > 2): ?>
                        <span class="dots">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?= $i ?>" class="page-link <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($end < $total_pages): ?>
                    <?php if ($end < $total_pages - 1): ?>
                        <span class="dots">...</span>
                    <?php endif; ?>
                    <a href="?page=<?= $total_pages ?>" class="page-link <?= $page == $total_pages ? 'active' : '' ?>">
                        <?= $total_pages ?>
                    </a>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="page-link">&gt;</a>
                <?php else: ?>
                    <span class="page-link disabled">&gt;</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="eventModal" class="event-modal-overlay" onclick="closeEventModal(event)">
        <div class="event-modal" onclick="event.stopPropagation()">
            <div id="eventModalBody"></div>
        </div>
    </div>

    <script src="/cardhaven/interface/event/event.js"></script>
</body>
</html>