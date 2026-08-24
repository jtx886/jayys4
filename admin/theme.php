<?php
// 管理后台 - 主题设置
$page_title = '主题设置';
$active_menu = 'theme';
require_once __DIR__ . '/includes/header.php';

$theme = get_theme_colors();
$primary = $theme['primary'];
$secondary = $theme['secondary'];

// 预设配色方案
$presets = [
    ['TMDB 蓝',      '#01B4E4', '#032541'],
    ['海洋蓝',        '#1890FF', '#0A1F44'],
    ['翡翠绿',        '#10B981', '#065F46'],
    ['活力橙',        '#F97316', '#7C2D12'],
    ['玫瑰红',        '#EC4899', '#831843'],
    ['梦幻紫',        '#8B5CF6', '#4C1D95'],
    ['深邃青',        '#14B8A6', '#134E4A'],
    ['樱花粉',        '#F472B6', '#831843'],
    ['日落红',        '#EF4444', '#7F1D1D'],
    ['经典黑',        '#374151', '#111827'],
    ['天空蓝',        '#0EA5E9', '#0C4A6E'],
    ['苹果绿',        '#84CC16', '#3F6212'],
];
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="icon icon-palette"></i> 主题颜色自定义</span>
        <span style="font-size:12px;color:var(--text-light);font-weight:400;">实时预览，保存后全站生效</span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:30px;">
        <!-- 配色方案 -->
        <div>
            <h4 style="margin-bottom:16px;font-size:15px;">预设配色方案</h4>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px;">
                <?php foreach ($presets as $idx => $p): ?>
                    <div onclick="selectPreset('<?= $p[1] ?>','<?= $p[2] ?>', this)"
                         class="preset-card <?= ($primary===$p[1]&&$secondary===$p[2])?'selected':'' ?>"
                         style="padding:14px;border-radius:12px;cursor:pointer;border:2px solid var(--border);transition:all 0.2s;background:#fff;">
                        <div style="display:flex;height:44px;border-radius:8px;overflow:hidden;margin-bottom:8px;">
                            <div style="flex:1;background:<?= $p[1] ?>;"></div>
                            <div style="flex:1;background:<?= $p[2] ?>;"></div>
                        </div>
                        <div style="font-size:13px;font-weight:600;text-align:center;"><?= $p[0] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 自定义颜色 -->
        <div>
            <h4 style="margin-bottom:16px;font-size:15px;">自定义颜色</h4>
            <div class="form-group">
                <label class="form-label">主色（按钮、链接、高亮）</label>
                <div style="display:flex;gap:12px;align-items:center;">
                    <input type="color" id="primaryColor" value="<?= e($primary) ?>" onchange="previewTheme()" style="width:60px;height:46px;border:none;border-radius:8px;cursor:pointer;background:transparent;">
                    <input type="text" id="primaryHex" class="form-input" value="<?= e($primary) ?>" pattern="#[0-9A-Fa-f]{6}" maxlength="7" oninput="previewTheme()">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">深色（导航栏、头部背景）</label>
                <div style="display:flex;gap:12px;align-items:center;">
                    <input type="color" id="secondaryColor" value="<?= e($secondary) ?>" onchange="previewTheme()" style="width:60px;height:46px;border:none;border-radius:8px;cursor:pointer;background:transparent;">
                    <input type="text" id="secondaryHex" class="form-input" value="<?= e($secondary) ?>" pattern="#[0-9A-Fa-f]{6}" maxlength="7" oninput="previewTheme()">
                </div>
            </div>

            <h4 style="margin:28px 0 16px;font-size:15px;">实时预览</h4>
            <div id="previewBox" style="border-radius:12px;overflow:hidden;border:1px solid var(--border);">
                <div id="prevHeader" style="padding:14px 18px;background:var(--primary-dark);color:#fff;display:flex;align-items:center;gap:12px;">
                    <div id="prevLogo" style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,var(--primary),#8edb52);display:flex;align-items:center;justify-content:center;font-weight:800;color:var(--primary-dark);">J</div>
                    <div style="font-weight:700;">Jay影视 - 预览</div>
                    <div style="margin-left:auto;display:flex;gap:8px;">
                        <div id="prevBtn1" style="padding:6px 14px;border-radius:6px;background:rgba(255,255,255,0.1);font-size:12px;">菜单</div>
                        <div id="prevBtn2" style="padding:6px 14px;border-radius:6px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));font-size:12px;">主按钮</div>
                    </div>
                </div>
                <div style="padding:20px;background:#fff;">
                    <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        <div id="prevCard1" style="flex:1;min-width:120px;aspect-ratio:2/3;border-radius:8px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));opacity:0.85;"></div>
                        <div id="prevCard2" style="flex:1;min-width:120px;aspect-ratio:2/3;border-radius:8px;background:var(--bg-gray);border:1px solid var(--border);"></div>
                        <div id="prevCard3" style="flex:1;min-width:120px;aspect-ratio:2/3;border-radius:8px;background:linear-gradient(135deg,var(--primary-dark),var(--primary));opacity:0.7;"></div>
                    </div>
                    <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
                        <div style="padding:10px 20px;border-radius:999px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:600;font-size:12px;">活动标签</div>
                        <div style="padding:10px 20px;border-radius:999px;border:1px solid var(--border);font-size:12px;color:var(--text-secondary);">普通标签</div>
                        <div style="padding:10px 20px;border-radius:999px;border:1px solid var(--primary);color:var(--primary);font-size:12px;">选中</div>
                    </div>
                </div>
            </div>

            <div style="margin-top:24px;display:flex;gap:12px;">
                <button class="btn btn-primary btn-lg" onclick="saveTheme()">
                    <i class="icon icon-palette" style="color:#fff;"></i> 保存主题设置
                </button>
                <button class="btn btn-outline btn-lg" onclick="selectPreset('#01B4E4','#032541')">恢复默认</button>
            </div>
        </div>
    </div>
</div>

<script>
function selectPreset(p, s, el) {
    document.querySelectorAll('.preset-card').forEach(function(c){c.style.borderColor='var(--border)';c.style.background='#fff';});
    if (el) { el.style.borderColor = 'var(--primary)'; el.style.background = '#e0f2fe'; }
    document.getElementById('primaryColor').value = p;
    document.getElementById('secondaryColor').value = s;
    document.getElementById('primaryHex').value = p;
    document.getElementById('secondaryHex').value = s;
    previewTheme();
}
function previewTheme() {
    var p = document.getElementById('primaryColor').value || document.getElementById('primaryHex').value;
    var s = document.getElementById('secondaryColor').value || document.getElementById('secondaryHex').value;
    if (/^#[0-9A-Fa-f]{6}$/.test(p)) {
        document.getElementById('primaryColor').value = p;
        document.getElementById('primaryHex').value = p;
    }
    if (/^#[0-9A-Fa-f]{6}$/.test(s)) {
        document.getElementById('secondaryColor').value = s;
        document.getElementById('secondaryHex').value = s;
    }
    // 预览框动态CSS变量
    document.getElementById('previewBox').style.setProperty('--primary', p);
    document.getElementById('previewBox').style.setProperty('--primary-dark', s);
    document.getElementById('previewBox').style.setProperty('--primary-gradient', 'linear-gradient(135deg,'+p+','+s+')');
    document.getElementById('prevHeader').style.background = s;
    document.getElementById('prevLogo').style.background = 'linear-gradient(135deg,'+p+',#8edb52)';
    document.getElementById('prevBtn2').style.background = 'linear-gradient(135deg,'+p+','+s+')';
}
previewTheme();
function saveTheme() {
    var p = document.getElementById('primaryHex').value;
    var s = document.getElementById('secondaryHex').value;
    if (!/^#[0-9A-Fa-f]{6}$/.test(p) || !/^#[0-9A-Fa-f]{6}$/.test(s)) {
        alert('颜色格式必须是 #RRGGBB，例如 #01B4E4'); return;
    }
    var fd = new FormData();
    fd.append('action', 'save_theme');
    fd.append('primary', p);
    fd.append('secondary', s);
    fetch('/api/admin_api.php', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(res=>{
            alert(res.message);
            if (res.code === 0) setTimeout(function(){location.reload();}, 400);
        });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
