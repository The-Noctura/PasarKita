<?php
/** @var string $title */
/** @var array|null $user */
/** @var string|null $success */
/** @var string|null $error */
/** @var array $addresses */

$auth = auth_user();
$u = isset($user) && is_array($user) ? $user : null;
$addresses = isset($addresses) && is_array($addresses) ? $addresses : [];

ob_start();
?>
<main class="pt-0">
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-50 via-white to-white"></div>
        <div class="relative max-w-[1100px] mx-auto px-4 py-10 md:py-14">
            <div class="reveal flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div>
                    <span class="inline-block text-xs tracking-widest uppercase text-emerald-700/80 bg-emerald-600/10 px-3 py-1 rounded-full mb-4">Akun</span>
                    <h1 class="text-2xl md:text-4xl font-semibold text-[#1f1f1f]">Akun Saya</h1>
                </div>
            </div>

            <?php if (is_string($success) && $success !== ''): ?>
                <div class="reveal mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <?php if (is_string($error) && $error !== ''): ?>
                <div class="reveal mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="reveal mt-8 grid gap-6">
                <div class="rounded-3xl border border-gray-200/70 bg-white/85 backdrop-blur p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <h2 class="text-lg font-semibold text-[#1f1f1f]">Profil</h2>
                    </div>

                    <?php if (!$auth || !$u): ?>
                        <p class="mt-3 text-sm text-[#595959]">Data akun tidak ditemukan.</p>
                    <?php else: ?>
                        <form class="mt-4" method="POST" action="<?= e(url('/account/update')) ?>">
                            <?= csrf_field() ?>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                    <label class="block text-xs uppercase tracking-widest text-gray-600" for="full_name">Nama</label>
                                    <input id="full_name" name="full_name" type="text" value="<?= e((string)($u['full_name'] ?? ($auth['name'] ?? ''))) ?>" class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-[#1f1f1f] focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                                </div>

                                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                    <label class="block text-xs uppercase tracking-widest text-gray-600" for="username">Username</label>
                                    <input id="username" type="text" value="@<?= e((string)($u['username'] ?? ($auth['username'] ?? ''))) ?>" class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-[#1f1f1f]" disabled />
                                    <p class="mt-2 text-xs text-gray-600">*Hubungi admin untuk perubahan username.</p>
                                </div>

                                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                    <label class="block text-xs uppercase tracking-widest text-gray-600" for="email">Email</label>
                                    <input id="email" type="email" value="<?= e((string)($u['email'] ?? ($auth['email'] ?? ''))) ?>" class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-[#1f1f1f]" disabled />
                                    <p class="mt-2 text-xs text-gray-600">*Hubungi admin untuk perubahan email.</p>
                                </div>

                                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                    <label class="block text-xs uppercase tracking-widest text-gray-600" for="phone">No. HP</label>
                                    <input id="phone" type="text" value="<?= e((string)($u['phone'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-[#1f1f1f]" disabled />
                                    <p class="mt-2 text-xs text-gray-600">*Hubungi admin untuk perubahan nomor.</p>
                                </div>

                                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:col-span-2">
                                    <label class="block text-xs uppercase tracking-widest text-gray-600" for="birth_date">Tanggal Lahir</label>
                                    <input id="birth_date" type="text" value="<?= e((string)($u['birth_date'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-[#1f1f1f]" disabled />
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-end">
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 text-white px-6 py-3 shadow-sm hover:bg-emerald-700 transition">Simpan</button>
                            </div>
                        </form>
                    <?php endif; ?>
            </div>

            <div id="alamat" class="reveal mt-6 rounded-3xl border border-gray-200/70 bg-white/85 backdrop-blur p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-[#1f1f1f]">Alamat Saya</h2>
                        <p class="mt-1 text-sm text-[#4b4b4b]">Kelola alamat pengiriman untuk checkout lebih cepat.</p>
                    </div>
                    <button type="button" id="btnAddAddress" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 text-white px-4 py-2 text-sm shadow-sm hover:bg-emerald-700 transition">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-white/15">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14" />
                                <path d="M5 12h14" />
                            </svg>
                        </span>
                        Tambah Alamat Baru
                    </button>
                </div>

                <?php if (!$addresses): ?>
                    <div class="mt-5 rounded-2xl border border-gray-200 bg-white px-4 py-5 text-sm text-[#595959]">
                        Belum ada alamat tersimpan. Tambahkan alamat untuk mempermudah checkout.
                    </div>
                <?php else: ?>
                    <div class="mt-5 space-y-3">
                        <?php foreach ($addresses as $a): ?>
                            <?php
                            $aid = (int) ($a['id'] ?? 0);
                            $isPrimary = !empty($a['is_primary']);
                            $label = (string) ($a['label'] ?? 'home');
                            $labelText = $label === 'office' ? 'Kantor' : 'Rumah';
                            $fullLine = trim((string) ($a['street'] ?? ''));
                            $regionLine = trim((string) ($a['region'] ?? ''));
                            $detailLine = trim((string) ($a['detail'] ?? ''));
                            ?>
                            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4">
                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="font-semibold text-[#1f1f1f]">
                                                <?= e((string) ($a['recipient_name'] ?? '')) ?>
                                                <span class="font-normal text-gray-600">(<?= e((string) ($a['phone'] ?? '')) ?>)</span>
                                            </div>
                                            <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-700 px-2.5 py-1 text-xs">
                                                <?= e($labelText) ?>
                                            </span>
                                            <?php if ($isPrimary): ?>
                                                <span class="inline-flex items-center rounded-full bg-emerald-600 text-white px-2.5 py-1 text-xs">Utama</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-2 text-sm text-[#4b4b4b] leading-relaxed break-words">
                                            <div><?= e($fullLine) ?></div>
                                            <div><?= e($regionLine) ?></div>
                                            <?php if ($detailLine !== ''): ?>
                                                <div class="text-gray-600"><?= e($detailLine) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="w-full md:w-auto flex flex-col sm:flex-row gap-2 shrink-0">
                                        <?php if (!$isPrimary): ?>
                                            <form method="POST" action="<?= e(url('/account/address/primary')) ?>" class="w-full sm:w-auto">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="address_id" value="<?= $aid ?>" />
                                                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-emerald-600/30 text-[#2b2b2b] px-4 py-2 text-sm hover:border-emerald-600 hover:bg-emerald-50 transition">Atur sebagai utama</button>
                                            </form>
                                        <?php endif; ?>

                                        <button
                                            type="button"
                                            class="btnEditAddress w-full sm:w-auto inline-flex items-center justify-center rounded-xl text-[#2b2b2b] px-4 py-2 text-sm hover:bg-emerald-50 transition"
                                            data-id="<?= $aid ?>"
                                            data-label="<?= e((string) ($a['label'] ?? 'home')) ?>"
                                            data-recipient="<?= e((string) ($a['recipient_name'] ?? '')) ?>"
                                            data-phone="<?= e((string) ($a['phone'] ?? '')) ?>"
                                            data-region="<?= e((string) ($a['region'] ?? '')) ?>"
                                            data-street="<?= e((string) ($a['street'] ?? '')) ?>"
                                            data-detail="<?= e((string) ($a['detail'] ?? '')) ?>"
                                            data-primary="<?= $isPrimary ? '1' : '0' ?>"
                                        >Ubah</button>

                                        <form method="POST" action="<?= e(url('/account/address/delete')) ?>" class="jsDeleteAddressForm w-full sm:w-auto">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="address_id" value="<?= $aid ?>" />
                                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl text-rose-700 px-4 py-2 text-sm hover:bg-rose-50 transition">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<div id="addressModal" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-black/50" data-close></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-[720px] rounded-2xl bg-white shadow-xl overflow-hidden">
            <div class="px-6 py-5 border-b">
                <div class="text-xl font-semibold text-[#1f1f1f]" id="addressModalTitle">Alamat Baru</div>
            </div>

            <form id="addressForm" method="POST" action="<?= e(url('/account/address/save')) ?>" class="px-6 py-5">
                <?= csrf_field() ?>
                <input type="hidden" name="address_id" id="address_id" value="0" />
                <input type="hidden" name="label" id="address_label" value="home" />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <input
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-[#1f1f1f] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        type="text" name="recipient_name" id="recipient_name" placeholder="Nama Lengkap" required
                    />
                    <input
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-[#1f1f1f] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        type="text" name="phone" id="address_phone" placeholder="Nomor Telepon" required
                    />
                </div>

                <div class="mt-4">
                    <input
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-[#1f1f1f] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        type="text" name="region" id="region" placeholder="Provinsi, Kota/Kabupaten, Kecamatan, Kode Pos" required readonly
                    />
                </div>

                <div class="mt-4 rounded-2xl border border-gray-200 bg-white px-4 py-4">
                   

                    <div class="mt-3">
                        <div class="relative border-b border-gray-200">
                            <div class="grid grid-cols-4 gap-1 text-xs sm:text-sm">
                                <div id="wil_head_0" class="min-w-0 overflow-hidden py-2 text-center font-medium text-emerald-600 cursor-pointer select-none">Provinsi</div>
                                <div id="wil_head_1" class="min-w-0 overflow-hidden py-2 text-center font-medium text-gray-600 cursor-pointer select-none">Kota</div>
                                <div id="wil_head_2" class="min-w-0 overflow-hidden py-2 text-center font-medium text-gray-600 cursor-pointer select-none">
                                    <span class="sm:hidden">Kec.</span>
                                    <span class="hidden sm:inline">Kecamatan</span>
                                </div>
                                <div id="wil_head_3" class="min-w-0 overflow-hidden py-2 text-center font-medium text-gray-600 cursor-pointer select-none">
                                    <span class="sm:hidden">Pos</span>
                                    <span class="hidden sm:inline">Kode Pos</span>
                                </div>
                            </div>
                            <div id="wil_indicator" class="absolute bottom-0 left-0 h-[2px] w-1/4 bg-emerald-600 transition-transform duration-300 ease-out" style="transform: translateX(0%);"></div>
                        </div>

                        <div class="mt-3">
                            <div id="wil_panel_0">
                                <label class="block text-xs uppercase tracking-widest text-gray-600" for="wil_province">Provinsi</label>
                                <select id="wil_province" class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-[#1f1f1f] focus:outline-none focus:ring-2 focus:ring-emerald-200"></select>
                            </div>
                            <div id="wil_panel_1" class="hidden">
                                <label class="block text-xs uppercase tracking-widest text-gray-600" for="wil_regency">Kota/Kabupaten</label>
                                <select id="wil_regency" class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-[#1f1f1f] focus:outline-none focus:ring-2 focus:ring-emerald-200" disabled></select>
                            </div>
                            <div id="wil_panel_2" class="hidden">
                                <label class="block text-xs uppercase tracking-widest text-gray-600" for="wil_district">Kecamatan</label>
                                <select id="wil_district" class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-[#1f1f1f] focus:outline-none focus:ring-2 focus:ring-emerald-200" disabled></select>
                            </div>
                            <div id="wil_panel_3" class="hidden">
                                <label class="block text-xs uppercase tracking-widest text-gray-600" for="wil_postal">Kode Pos</label>
                                <input id="wil_postal" type="text" inputmode="numeric" autocomplete="postal-code" pattern="[0-9]{5}" maxlength="5" class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-[#1f1f1f] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200" placeholder="Masukkan 5 digit" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <input
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-[#1f1f1f] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        type="text" name="street" id="street" placeholder="Nama Jalan, Gedung, No. Rumah" required
                    />
                </div>

                <div class="mt-4">
                    <input
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-[#1f1f1f] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        type="text" name="detail" id="detail" placeholder="Detail Lainnya (Cth: Blok / Unit No., Patokan)"
                    />
                </div>

                <div class="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-[#4b4b4b]">Tandai sebagai:</span>
                        <button type="button" class="btnLabel px-4 py-2 rounded-xl border border-gray-200 text-sm hover:bg-emerald-50 transition" data-value="home">Rumah</button>
                        <button type="button" class="btnLabel px-4 py-2 rounded-xl border border-gray-200 text-sm hover:bg-emerald-50 transition" data-value="office">Kantor</button>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-[#4b4b4b]">
                        <input type="checkbox" name="is_primary" value="1" id="is_primary" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-200" />
                        Jadikan alamat utama
                    </label>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" class="rounded-xl px-4 py-2 text-sm text-[#4b4b4b] hover:bg-emerald-50" data-close>Nanti Saja</button>
                    <button type="submit" class="rounded-xl bg-emerald-600 text-white px-6 py-2 text-sm hover:bg-emerald-700">OK</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="deleteAddressModal" class="fixed inset-0 z-[110] hidden">
    <div class="absolute inset-0 bg-black/50" data-close></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-[480px] rounded-2xl bg-white shadow-xl overflow-hidden">
            <div class="px-6 py-5 border-b">
                <div class="text-lg font-semibold text-[#1f1f1f]">Hapus Alamat?</div>
            </div>
            <div class="px-6 py-5">
                <p class="text-sm text-[#4b4b4b]">Alamat ini akan dihapus permanen. Lanjutkan?</p>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" id="btnCancelDeleteAddress" class="rounded-xl px-4 py-2 text-sm text-[#4b4b4b] hover:bg-emerald-50">Batal</button>
                    <button type="button" id="btnConfirmDeleteAddress" class="rounded-xl bg-rose-600 text-white px-6 py-2 text-sm hover:bg-rose-700">Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('addressModal');
        if (!modal) return;

        var deleteModal = document.getElementById('deleteAddressModal');
        var deleteCancelBtn = document.getElementById('btnCancelDeleteAddress');
        var deleteConfirmBtn = document.getElementById('btnConfirmDeleteAddress');
        var pendingDeleteForm = null;

        // Portal modal to body to avoid clipping.
        document.addEventListener('DOMContentLoaded', function () {
            try { document.body.appendChild(modal); } catch (e) {}
            try { deleteModal && document.body.appendChild(deleteModal); } catch (e) {}
        });

        var titleEl = document.getElementById('addressModalTitle');
        var form = document.getElementById('addressForm');
        var idEl = document.getElementById('address_id');
        var labelEl = document.getElementById('address_label');
        var recipientEl = document.getElementById('recipient_name');
        var phoneEl = document.getElementById('address_phone');
        var regionEl = document.getElementById('region');
        var streetEl = document.getElementById('street');
        var detailEl = document.getElementById('detail');
        var primaryEl = document.getElementById('is_primary');
        var labelButtons = Array.prototype.slice.call(document.querySelectorAll('#addressModal .btnLabel'));

        // Wilayah stepper (Provinsi -> Kota/Kab -> Kecamatan -> Kode Pos)
        var WIL_BASE = 'https://www.emsifa.com/api-wilayah-indonesia/api';

        var wilIndicator = document.getElementById('wil_indicator');
        var wilBackBtn = document.getElementById('wil_back');
        var wilHead0 = document.getElementById('wil_head_0');
        var wilHead1 = document.getElementById('wil_head_1');
        var wilHead2 = document.getElementById('wil_head_2');
        var wilHead3 = document.getElementById('wil_head_3');

        var wilPanel0 = document.getElementById('wil_panel_0');
        var wilPanel1 = document.getElementById('wil_panel_1');
        var wilPanel2 = document.getElementById('wil_panel_2');
        var wilPanel3 = document.getElementById('wil_panel_3');

        var wilProv = document.getElementById('wil_province');
        var wilReg = document.getElementById('wil_regency');
        var wilDis = document.getElementById('wil_district');
        var wilPos = document.getElementById('wil_postal');

        var wilIndex = 0;
        var provincesCache = null;
        var regenciesCache = null;
        var districtsCache = null;

        function selectedText(sel) {
            if (!sel || !sel.options || sel.selectedIndex < 0) return '';
            var opt = sel.options[sel.selectedIndex];
            if (!opt) return '';
            return (opt.textContent || opt.innerText || '').trim();
        }

        function setSlide(index) {
            wilIndex = Math.max(0, Math.min(3, parseInt(index || 0, 10) || 0));

            if (wilPanel0) wilPanel0.classList.toggle('hidden', wilIndex !== 0);
            if (wilPanel1) wilPanel1.classList.toggle('hidden', wilIndex !== 1);
            if (wilPanel2) wilPanel2.classList.toggle('hidden', wilIndex !== 2);
            if (wilPanel3) wilPanel3.classList.toggle('hidden', wilIndex !== 3);

            if (wilIndicator) {
                wilIndicator.style.transform = 'translateX(' + (wilIndex * 100) + '%)';
            }

            if (wilHead0) wilHead0.classList.toggle('text-emerald-600', wilIndex === 0);
            if (wilHead0) wilHead0.classList.toggle('text-gray-600', wilIndex !== 0);
            if (wilHead1) wilHead1.classList.toggle('text-emerald-600', wilIndex === 1);
            if (wilHead1) wilHead1.classList.toggle('text-gray-600', wilIndex !== 1);
            if (wilHead2) wilHead2.classList.toggle('text-emerald-600', wilIndex === 2);
            if (wilHead2) wilHead2.classList.toggle('text-gray-600', wilIndex !== 2);
            if (wilHead3) wilHead3.classList.toggle('text-emerald-600', wilIndex === 3);
            if (wilHead3) wilHead3.classList.toggle('text-gray-600', wilIndex !== 3);

            if (wilBackBtn) {
                wilBackBtn.classList.toggle('hidden', wilIndex === 0);
            }
        }

        function resetSelect(sel, placeholder) {
            if (!sel) return;
            sel.innerHTML = '';
            var opt = document.createElement('option');
            opt.value = '';
            opt.textContent = placeholder || 'Pilih';
            sel.appendChild(opt);
            sel.value = '';
        }

        function setSelectDisabled(sel, disabled) {
            if (!sel) return;
            sel.disabled = !!disabled;
        }

        function fillSelect(sel, items, placeholder) {
            resetSelect(sel, placeholder);
            if (!sel) return;

            var sorted = (items || []).slice().sort(function (a, b) {
                var nameA = String((a && a.name) ? a.name : '');
                var nameB = String((b && b.name) ? b.name : '');
                // Prefer Indonesian collation when available.
                try {
                    return nameA.localeCompare(nameB, 'id', { sensitivity: 'base' });
                } catch (e) {
                    return nameA.localeCompare(nameB);
                }
            });

            sorted.forEach(function (it) {
                if (!it) return;
                var opt = document.createElement('option');
                opt.value = String(it.id);
                opt.textContent = String(it.name);
                sel.appendChild(opt);
            });
        }

        function fetchJson(url) {
            return fetch(url, { credentials: 'omit' }).then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            });
        }

        function clearRegionIfNew() {
            if (!regionEl) return;
            // If region is already filled (edit), keep it; otherwise start blank.
            if (!regionEl.value) regionEl.value = '';
        }

        function updateRegionValue() {
            if (!regionEl) return;
            var provName = selectedText(wilProv);
            var regName = selectedText(wilReg);
            var disName = selectedText(wilDis);
            var pos = wilPos ? String(wilPos.value || '').trim() : '';

            if (provName && regName && disName && pos) {
                regionEl.value = provName + ', ' + regName + ', ' + disName + ', ' + pos;
            }
        }

        function wilayah_reset() {
            provincesCache = null;
            regenciesCache = null;
            districtsCache = null;

            resetSelect(wilProv, 'Pilih Provinsi');
            resetSelect(wilReg, 'Pilih Kota/Kabupaten');
            resetSelect(wilDis, 'Pilih Kecamatan');

            setSelectDisabled(wilReg, true);
            setSelectDisabled(wilDis, true);

            if (wilPos) wilPos.value = '';
            setSlide(0);
        }

        function loadProvinces() {
            if (provincesCache) {
                fillSelect(wilProv, provincesCache, 'Pilih Provinsi');
                setSelectDisabled(wilProv, false);
                return Promise.resolve(provincesCache);
            }
            return fetchJson(WIL_BASE + '/provinces.json').then(function (items) {
                provincesCache = Array.isArray(items) ? items : [];
                fillSelect(wilProv, provincesCache, 'Pilih Provinsi');
                setSelectDisabled(wilProv, false);
                return provincesCache;
            });
        }

        function loadRegencies(provinceId) {
            if (!provinceId) return Promise.resolve([]);
            return fetchJson(WIL_BASE + '/regencies/' + encodeURIComponent(String(provinceId)) + '.json').then(function (items) {
                regenciesCache = Array.isArray(items) ? items : [];
                fillSelect(wilReg, regenciesCache, 'Pilih Kota/Kabupaten');
                setSelectDisabled(wilReg, false);
                return regenciesCache;
            });
        }

        function loadDistricts(regencyId) {
            if (!regencyId) return Promise.resolve([]);
            return fetchJson(WIL_BASE + '/districts/' + encodeURIComponent(String(regencyId)) + '.json').then(function (items) {
                districtsCache = Array.isArray(items) ? items : [];
                fillSelect(wilDis, districtsCache, 'Pilih Kecamatan');
                setSelectDisabled(wilDis, false);
                return districtsCache;
            });
        }

        function selectByText(sel, items, text) {
            if (!sel || !Array.isArray(items) || !text) return false;
            var t = String(text || '').trim().toLowerCase();
            var found = items.find(function (it) {
                var n = String((it && it.name) ? it.name : '').trim().toLowerCase();
                return n === t;
            });
            if (!found) return false;
            sel.value = String(found.id);
            return true;
        }

        function parseRegionParts(regionStr) {
            var raw = String(regionStr || '').trim();
            if (!raw) return null;
            var parts = raw.split(',').map(function (s) { return String(s || '').trim(); }).filter(Boolean);
            if (parts.length < 3) return null;

            var postal = '';
            if (parts.length >= 4) {
                postal = parts[3];
            } else {
                var m = raw.match(/(\d{5})\s*$/);
                postal = m ? m[1] : '';
            }

            return {
                province: parts[0] || '',
                regency: parts[1] || '',
                district: parts[2] || '',
                postal: postal || ''
            };
        }

        function wilayah_prefill_from_region(regionStr) {
            var parts = parseRegionParts(regionStr);
            if (!parts) return Promise.resolve(false);

            return loadProvinces().then(function () {
                var okProv = selectByText(wilProv, provincesCache, parts.province);
                if (!okProv) return false;

                setSlide(1);
                setSelectDisabled(wilReg, true);
                setSelectDisabled(wilDis, true);
                if (wilPos) wilPos.value = '';

                return loadRegencies(wilProv.value).then(function () {
                    var okReg = selectByText(wilReg, regenciesCache, parts.regency);
                    if (!okReg) return false;

                    setSlide(2);
                    setSelectDisabled(wilDis, true);
                    if (wilPos) wilPos.value = '';

                    return loadDistricts(wilReg.value).then(function () {
                        var okDis = selectByText(wilDis, districtsCache, parts.district);
                        if (!okDis) return false;

                        setSlide(3);
                        if (wilPos) wilPos.value = parts.postal || '';
                        updateRegionValue();
                        return true;
                    });
                });
            }).catch(function () {
                if (regionEl) {
                    regionEl.readOnly = false;
                    regionEl.placeholder = '';
                }
                return false;
            });
        }

        function wilayah_init(regionStr) {
            if (!wilProv || !wilReg || !wilDis || !wilPos) {
                return;
            }

            if (regionEl) {
                regionEl.readOnly = true;
            }

            wilayah_reset();
            clearRegionIfNew();

            loadProvinces().catch(function () {
                if (regionEl) {
                    regionEl.readOnly = false;
                    regionEl.placeholder = '';
                }
            });

            if (typeof regionStr === 'string' && regionStr.trim() !== '') {
                // Best-effort prefill for edit.
                wilayah_prefill_from_region(regionStr);
            }
        }

        if (wilBackBtn) {
            wilBackBtn.addEventListener('click', function () {
                setSlide(Math.max(0, wilIndex - 1));
            });
        }

        function canGoStep(targetIndex) {
            var idx = parseInt(targetIndex || 0, 10) || 0;
            if (idx <= 0) return true;
            if (idx === 1) return !!(wilProv && wilProv.value);
            if (idx === 2) return !!(wilProv && wilProv.value) && !!(wilReg && wilReg.value);
            if (idx === 3) return !!(wilProv && wilProv.value) && !!(wilReg && wilReg.value) && !!(wilDis && wilDis.value);
            return false;
        }

        function goStep(targetIndex) {
            var idx = Math.max(0, Math.min(3, parseInt(targetIndex || 0, 10) || 0));
            if (!canGoStep(idx)) return;

            if (idx === 1) {
                setSlide(1);
                if (wilReg && wilReg.disabled && wilProv && wilProv.value) {
                    loadRegencies(wilProv.value).catch(function () {
                        if (regionEl) regionEl.readOnly = false;
                    });
                }
                return;
            }

            if (idx === 2) {
                setSlide(2);
                if (wilDis && wilDis.disabled && wilReg && wilReg.value) {
                    loadDistricts(wilReg.value).catch(function () {
                        if (regionEl) regionEl.readOnly = false;
                    });
                }
                return;
            }

            setSlide(idx);
        }

        // Allow direct navigation by clicking the header steps.
        if (wilHead0) wilHead0.addEventListener('click', function () { goStep(0); });
        if (wilHead1) wilHead1.addEventListener('click', function () { goStep(1); });
        if (wilHead2) wilHead2.addEventListener('click', function () { goStep(2); });
        if (wilHead3) wilHead3.addEventListener('click', function () { goStep(3); });

        if (wilProv) {
            wilProv.addEventListener('change', function () {
                resetSelect(wilReg, 'Pilih Kota/Kabupaten');
                resetSelect(wilDis, 'Pilih Kecamatan');
                setSelectDisabled(wilReg, true);
                setSelectDisabled(wilDis, true);
                if (wilPos) wilPos.value = '';
                if (regionEl) regionEl.value = '';

                var pid = wilProv.value;
                if (!pid) {
                    setSlide(0);
                    return;
                }

                setSlide(1);
                loadRegencies(pid).catch(function () {
                    if (regionEl) regionEl.readOnly = false;
                });
            });
        }

        if (wilReg) {
            wilReg.addEventListener('change', function () {
                resetSelect(wilDis, 'Pilih Kecamatan');
                setSelectDisabled(wilDis, true);
                if (wilPos) wilPos.value = '';
                if (regionEl) regionEl.value = '';

                var rid = wilReg.value;
                if (!rid) {
                    setSlide(1);
                    return;
                }

                setSlide(2);
                loadDistricts(rid).catch(function () {
                    if (regionEl) regionEl.readOnly = false;
                });
            });
        }

        if (wilDis) {
            wilDis.addEventListener('change', function () {
                if (wilPos) wilPos.value = '';
                if (regionEl) regionEl.value = '';

                var did = wilDis.value;
                if (!did) {
                    setSlide(2);
                    return;
                }

                setSlide(3);
            });
        }

        if (wilPos) {
            function sanitizePostalInput() {
                var v = String(wilPos.value || '');
                // Digits only, max 5 characters.
                v = v.replace(/\D+/g, '').slice(0, 5);
                if (wilPos.value !== v) wilPos.value = v;
            }

            wilPos.addEventListener('input', function () {
                sanitizePostalInput();
                if (regionEl) regionEl.value = '';
                updateRegionValue();
            });
            wilPos.addEventListener('change', function () {
                sanitizePostalInput();
                if (regionEl) regionEl.value = '';
                updateRegionValue();
            });
        }

        function setLabel(value) {
            if (!labelEl) return;
            labelEl.value = value === 'office' ? 'office' : 'home';
            labelButtons.forEach(function (btn) {
                var active = btn.getAttribute('data-value') === labelEl.value;
                btn.classList.toggle('bg-emerald-600', active);
                btn.classList.toggle('text-white', active);
                btn.classList.toggle('border-emerald-600/40', active);
            });
        }

        labelButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setLabel(btn.getAttribute('data-value') || 'home');
            });
        });

        function openModal(mode, data) {
            if (!modal) return;
            modal.classList.remove('hidden');
            document.documentElement.classList.add('overflow-hidden');

            if (titleEl) titleEl.textContent = mode === 'edit' ? 'Ubah Alamat' : 'Alamat Baru';
            if (idEl) idEl.value = data && data.id ? String(data.id) : '0';
            if (recipientEl) recipientEl.value = (data && data.recipient) ? data.recipient : '';
            if (phoneEl) phoneEl.value = (data && data.phone) ? data.phone : '';
            if (regionEl) regionEl.value = (data && data.region) ? data.region : '';
            if (streetEl) streetEl.value = (data && data.street) ? data.street : '';
            if (detailEl) detailEl.value = (data && data.detail) ? data.detail : '';
            if (primaryEl) primaryEl.checked = (data && data.primary === '1');
            setLabel((data && data.label) ? data.label : 'home');

            // Initialize wilayah stepper. For create, regionEl is empty and will be filled automatically.
            wilayah_init((data && data.region) ? data.region : '');

            setTimeout(function () {
                try { recipientEl && recipientEl.focus(); } catch (e) {}
            }, 0);
        }

        function closeModal() {
            modal.classList.add('hidden');
            document.documentElement.classList.remove('overflow-hidden');
            if (form) form.reset();
            if (idEl) idEl.value = '0';
            setLabel('home');

            // Reset wilayah UI.
            wilayah_reset();
        }

        function openDeleteModal(formEl) {
            if (!deleteModal) return;
            pendingDeleteForm = formEl || null;
            deleteModal.classList.remove('hidden');
            document.documentElement.classList.add('overflow-hidden');
            setTimeout(function () {
                try { deleteCancelBtn && deleteCancelBtn.focus(); } catch (e) {}
            }, 0);
        }

        function closeDeleteModal() {
            if (!deleteModal) return;
            deleteModal.classList.add('hidden');
            pendingDeleteForm = null;
            // Only remove overflow lock if address modal isn't open.
            if (modal && modal.classList.contains('hidden')) {
                document.documentElement.classList.remove('overflow-hidden');
            }
        }

        modal.addEventListener('click', function (e) {
            var target = e.target;
            if (target && target.hasAttribute && target.hasAttribute('data-close')) {
                closeModal();
            }
        });

        if (deleteModal) {
            deleteModal.addEventListener('click', function (e) {
                var target = e.target;
                if (target && target.hasAttribute && target.hasAttribute('data-close')) {
                    closeDeleteModal();
                }
            });
        }

        if (deleteCancelBtn) {
            deleteCancelBtn.addEventListener('click', function () {
                closeDeleteModal();
            });
        }

        if (deleteConfirmBtn) {
            deleteConfirmBtn.addEventListener('click', function () {
                if (pendingDeleteForm && pendingDeleteForm.submit) {
                    pendingDeleteForm.submit();
                }
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
            if (e.key === 'Escape' && deleteModal && !deleteModal.classList.contains('hidden')) {
                closeDeleteModal();
            }
        });

        var btnAdd = document.getElementById('btnAddAddress');
        if (btnAdd) {
            btnAdd.addEventListener('click', function () {
                openModal('create', {});
            });
        }

        Array.prototype.slice.call(document.querySelectorAll('.btnEditAddress')).forEach(function (btn) {
            btn.addEventListener('click', function () {
                openModal('edit', {
                    id: btn.getAttribute('data-id') || '0',
                    label: btn.getAttribute('data-label') || 'home',
                    recipient: btn.getAttribute('data-recipient') || '',
                    phone: btn.getAttribute('data-phone') || '',
                    region: btn.getAttribute('data-region') || '',
                    street: btn.getAttribute('data-street') || '',
                    detail: btn.getAttribute('data-detail') || '',
                    primary: btn.getAttribute('data-primary') || '0'
                });
            });
        });

        Array.prototype.slice.call(document.querySelectorAll('form.jsDeleteAddressForm')).forEach(function (f) {
            f.addEventListener('submit', function (e) {
                e.preventDefault();
                openDeleteModal(f);
            });
        });
    })();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
