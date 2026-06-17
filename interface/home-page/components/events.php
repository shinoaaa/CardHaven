<section class="home-section event-section">
    <div class="section-header">
        <h2 class="coolveticaa">Upcoming Events</h2>
    </div>
    <div class="event-grid">
        <?php foreach ($events as $event): ?>
        <div class="event-card">
            <img src="<?= $event['image_path'] ?>" alt="Event Image">
            <div class="event-info">
                <h3><?= htmlspecialchars($event['nama_event']) ?></h3>
                <p><?= is_object($event['tanggal_mulai']) ? $event['tanggal_mulai']->format('d M Y') : htmlspecialchars($event['tanggal_mulai']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>