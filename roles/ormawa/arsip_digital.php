<?php
/**
 * File: roles/ormawa/arsip_digital.php
 * Deskripsi: Pusat arsip digital ormawa (Proposal, LPJ, dan Surat-surat).
 */

check_role(['ormawa', 'bem', 'bpm']);
$user_id = $_SESSION['user_id'];

// Ambil data Proposal
$res_p = $conn->query("SELECT * FROM proposal_otomatis WHERE id_user_ormawa = $user_id ORDER BY tgl_dibuat DESC");
// Ambil data LPJ
$res_l = $conn->query("SELECT * FROM lpj_otomatis WHERE id_user_ormawa = $user_id ORDER BY tgl_dibuat DESC");
// Ambil data Surat Lain
$res_s = $conn->query("SELECT * FROM surat_otomatis WHERE id_user_ormawa = $user_id ORDER BY tgl_dibuat DESC");
?>

<div class="container-fluid px-4 py-4">
    <h1 class="h3 mb-4">Pusat Arsip Persuratan Digital</h1>

    <div class="card shadow-sm">
        <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs nav-fill border-0" id="archiveTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-3 fw-bold" id="proposal-tab" data-bs-toggle="tab" data-bs-target="#proposal" type="button" role="tab">
                        <i class="bi bi-file-earmark-text me-1"></i> Proposal
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold" id="lpj-tab" data-bs-toggle="tab" data-bs-target="#lpj" type="button" role="tab">
                        <i class="bi bi-check2-square me-1"></i> LPJ Otomatis
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold" id="surat-tab" data-bs-toggle="tab" data-bs-target="#surat" type="button" role="tab">
                        <i class="bi bi-file-earmark-medical me-1"></i> Surat Lainnya
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="archiveTabsContent">
                
                <!-- TAB PROPOSAL -->
                <div class="tab-pane fade show active" id="proposal" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama Kegiatan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($p = $res_p->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($p['tgl_dibuat'])); ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($p['nama_kegiatan']); ?></td>
                                    <td><span class="badge <?php echo $p['status'] == 'Final' ? 'bg-success' : 'bg-warning text-dark'; ?>"><?php echo $p['status']; ?></span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=view_proposal&id=<?php echo $p['id_proposal']; ?>" class="btn btn-info text-white" title="Cetak"><i class="bi bi-printer"></i></a>
                                            <a href="index.php?page=view_proposal&id=<?php echo $p['id_proposal']; ?>" class="btn btn-primary text-white" title="Download PDF"><i class="bi bi-download"></i></a>
                                            <?php if($p['status'] == 'Draft'): ?>
                                                <a href="index.php?page=edit_proposal&id=<?php echo $p['id_proposal']; ?>" class="btn btn-warning"><i class="bi bi-pencil"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; if($res_p->num_rows == 0) echo "<tr><td colspan='4' class='text-center py-4 text-muted'>Belum ada proposal.</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB LPJ -->
                <div class="tab-pane fade" id="lpj" role="tabpanel">
                     <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama Kegiatan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($l = $res_l->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($l['tgl_dibuat'])); ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($l['nama_kegiatan']); ?></td>
                                    <td><span class="badge <?php echo $l['status'] == 'Final' ? 'bg-success' : 'bg-warning text-dark'; ?>"><?php echo $l['status']; ?></span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=view_lpj_otomatis&id=<?php echo $l['id_lpj']; ?>" class="btn btn-info text-white" title="Cetak"><i class="bi bi-printer"></i></a>
                                            <a href="index.php?page=view_lpj_otomatis&id=<?php echo $l['id_lpj']; ?>" class="btn btn-primary text-white" title="Download PDF"><i class="bi bi-download"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; if($res_l->num_rows == 0) echo "<tr><td colspan='4' class='text-center py-4 text-muted'>Belum ada LPJ.</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB SURAT LAIN -->
                <div class="tab-pane fade" id="surat" role="tabpanel">
                     <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Perihal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($s = $res_s->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($s['tgl_dibuat'])); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $s['jenis_surat']; ?></span></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($s['perihal']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=view_surat_lain&id=<?php echo $s['id_surat']; ?>" class="btn btn-info text-white" title="Cetak"><i class="bi bi-printer"></i></a>
                                            <a href="index.php?page=view_surat_lain&id=<?php echo $s['id_surat']; ?>" class="btn btn-primary text-white" title="Download PDF"><i class="bi bi-download"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; if($res_s->num_rows == 0) echo "<tr><td colspan='4' class='text-center py-4 text-muted'>Belum ada surat lainnya.</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
