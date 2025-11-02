<?php
$bulan = 11;
$key = "$tahun-$bulan";
?>
<table class="table table-bordered table-sm text-center mb-0">
  <thead>
    <tr>
      <th>No</th>
      <th>Nama</th>
      <th>1</th>
      <th>2</th>
      <th>3</th>
      <th>4</th>
      <th>Total</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php $no=1; foreach($siswa as $s): 
      $total = 0;
    ?>
      <tr data-id="<?= $s['id'] ?>" data-bulan="<?= $bulan ?>">
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($s['nama']) ?></td>
        <?php for($i=1;$i<=4;$i++):
          $status = $pembayaran_data[$key][$s['id']][$i] ?? 0;
          if(!$status) $total += $bayar_per_minggu;
        ?>
          <td>
            <input type="checkbox" class="bayar-checkbox form-check-input"
              data-id="<?= $s['id'] ?>" data-minggu="<?= $i ?>" data-bulan="<?= $bulan ?>"
              <?= $status?'checked':'' ?>>
          </td>
        <?php endfor; ?>
        <td>
          <?= $total>0 ? "<span class='text-danger'>Rp ".number_format($total)."</span>" : "<span class='text-success fw-bold'>Lunas</span>" ?>
        </td>
        <td>
          <button class="btn btn-sm btn-primary btnEdit" data-id="<?= $s['id'] ?>"><i class="fa fa-pen"></i></button>
          <button class="btn btn-sm btn-danger btnDelete" data-id="<?= $s['id'] ?>"><i class="fa fa-trash"></i></button>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
