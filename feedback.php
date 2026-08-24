<?php
// 反馈页面
$page_title = '意见反馈';
require_once __DIR__ . '/includes/header.php';

$user = current_user();
$categories = [
    'bug' => '功能Bug',
    'suggest' => '功能建议',
    'content' => '内容问题',
    'play' => '播放问题',
    'account' => '账号问题',
    'other' => '其他问题'
];
?>

<section class="feedback-page">
    <div class="feedback-inner">
        <div class="feedback-header">
            <h2><i class="icon icon-message"></i> 意见反馈</h2>
            <?php if (!$user): ?>
                <a href="/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary btn-sm">登录后提交反馈</a>
            <?php endif; ?>
        </div>

        <!-- 提交反馈表单 -->
        <?php if ($user): ?>
        <div class="feedback-form-card">
            <h3 style="font-size:18px;margin-bottom:20px;font-weight:700;">提交新反馈</h3>
            <form id="feedbackForm" onsubmit="submitFeedback(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">标题</label>
                        <input type="text" name="title" class="form-input" placeholder="用一句话描述问题" required minlength="4" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">分类</label>
                        <select name="category" class="form-select" required>
                            <?php foreach ($categories as $k => $v): ?>
                                <option value="<?= e($k) ?>"><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">详细描述</label>
                    <textarea name="content" class="form-textarea" placeholder="请详细描述您遇到的问题或建议..." required minlength="10"></textarea>
                </div>
                <div id="fbFormError" style="display:none;" class="form-error mb-16"></div>
                <button type="submit" class="btn btn-primary">
                    <i class="icon icon-plus"></i> 提交反馈
                </button>
            </form>
        </div>
        <?php else: ?>
            <div class="flash-message warning" style="margin-bottom:24px;">
                请先 <a href="/login.php" style="color:var(--primary);font-weight:600;">登录</a> 后提交反馈。
            </div>
        <?php endif; ?>

        <!-- 反馈列表 -->
        <div>
            <h3 style="font-size:20px;font-weight:700;margin-bottom:20px;padding-left:4px;">用户反馈 (<?= $total ?? 0 ?>)</h3>
            <div class="feedback-list" id="feedbackList">
                <div style="padding:40px;text-align:center;color:var(--text-light);">
                    <div class="empty-icon" style="margin:0 auto 16px;"><i class="icon icon-message"></i></div>
                    加载中...
                </div>
            </div>
            <div id="fbPagination"></div>
        </div>
    </div>
</section>

<script>
var currentPage = 1;
var totalPages = 1;

function loadFeedbackList(page) {
    currentPage = page || 1;
    fetch('/api/feedback_api.php?page=' + currentPage)
        .then(r=>r.json())
        .then(res=>{
            if(res.code!==0){document.getElementById('feedbackList').innerHTML='<div style="padding:20px;color:var(--danger);">加载失败</div>';return;}
            totalPages = res.data.total_pages || 1;
            renderList(res.data.list);
            renderPagination();
        });
}

function renderList(list) {
    var box = document.getElementById('feedbackList');
    if (!list.length) {
        box.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-light);">暂无反馈内容</div>';
        return;
    }
    var html = '';
    list.forEach(function(fb){
        var replies = fb.replies || [];
        var showReplies = replies.length;
        var hasAdminReply = replies.some(function(r){return r.is_admin;});
        // 排序：管理员回复置顶
        var normalReplies = replies.filter(function(r){return !r.is_admin;});
        var adminReplies = replies.filter(function(r){return r.is_admin;});
        var allReplies = adminReplies.concat(normalReplies);
        var moreThan3 = allReplies.length > 3;
        var displayReplies = moreThan3 ? allReplies.slice(0, 3) : allReplies;

        var catMap = {bug:'功能Bug',suggest:'功能建议',content:'内容问题',play:'播放问题',account:'账号问题',other:'其他'};
        var stMap = {pending:['待处理','badge-yellow'],replied:['已回复','badge-blue'],resolved:['已解决','badge-green'],closed:['已关闭','badge-gray']};
        var st = stMap[fb.status] || ['未知','badge-gray'];
        var likeCls = fb.liked ? 'liked' : '';
        var likeIconCls = fb.liked ? 'icon-like active' : 'icon-like';

        html += '<div class="feedback-item" id="fb-' + fb.id + '">';
        html += '<div class="feedback-top">';
        html += '<div class="feedback-avatar">';
        if (fb.avatar) html += '<img src="' + fb.avatar + '">';
        else html += (fb.username || '').slice(0,1);
        html += '</div>';
        html += '<div class="feedback-body">';
        html += '<div class="feedback-author">' + escapeHtml(fb.username || '用户' + fb.user_id);
        if (fb.role === 'admin') html += ' <span class="dev-badge"></span>';
        html += '<span class="feedback-cat">' + (catMap[fb.category]||fb.category) + '</span>';
        html += '<span class="feedback-status ' + st[1] + '">' + st[0] + '</span>';
        html += '<span class="feedback-time">' + timeAgo(fb.created_at) + '</span>';
        html += '</div>';
        html += '<div class="feedback-title">' + escapeHtml(fb.title) + '</div>';
        html += '<div class="feedback-content-text">' + escapeHtml(fb.content).replace(/\n/g,'<br>') + '</div>';
        html += '</div></div>';
        html += '<div class="feedback-actions">';
        html += '<div class="feedback-like ' + likeCls + '" onclick="toggleLike(' + fb.id + ',this)">';
        html += '<i class="icon ' + likeIconCls + '"></i><span class="like-count">' + (fb.like_count || 0) + '</span> 赞</div>';
        html += '<div class="feedback-reply-btn" onclick="toggleReplyForm(' + fb.id + ',this)">';
        html += '<i class="icon icon-message" style="width:16px;height:16px;"></i> ' + replies.length + ' 回复</div>';
        html += '</div>';

        // 回复区
        if (showReplies > 0) {
            html += '<div class="replies-wrapper" id="replies-' + fb.id + '">';
            displayReplies.forEach(function(r){ html += renderReply(r); });
            if (moreThan3) {
                html += '<div class="replies-collapsed" id="collapsed-' + fb.id + '">';
                allReplies.slice(3).forEach(function(r){ html += renderReply(r); });
                html += '</div>';
                html += '<div class="replies-toggle" onclick="toggleCollapsed(' + fb.id + ',this)">展开全部回复（' + allReplies.length + '条）</div>';
            }
            html += '</div>';
        }
        // 回复表单
        <?php if ($user): ?>
        html += '<div class="reply-form" id="replyForm-' + fb.id + '" style="display:none;">';
        html += '<input type="text" placeholder="写下您的回复..." id="replyInput-' + fb.id + '">';
        html += '<button class="btn btn-primary btn-sm" onclick="submitReply(' + fb.id + ')">回复</button>';
        html += '</div>';
        <?php endif; ?>
        html += '</div>';
    });
    box.innerHTML = html;
}

function renderReply(r) {
    var h = '';
    h += '<div class="reply-item' + (r.is_admin ? ' admin-reply' : '') + '">';
    h += '<div class="reply-avatar">';
    if (r.avatar) h += '<img src="' + r.avatar + '" style="width:100%;height:100%;object-fit:cover;">';
    else h += (r.username || '').slice(0,1);
    h += '</div><div class="reply-body">';
    h += '<div class="reply-author">' + escapeHtml(r.username || '');
    if (r.is_admin || r.role === 'admin') h += ' <span class="dev-badge"></span>';
    h += ' <span class="feedback-time">' + timeAgo(r.created_at) + '</span></div>';
    h += '<div class="reply-content">' + escapeHtml(r.content).replace(/\n/g,'<br>') + '</div>';
    h += '</div></div>';
    return h;
}

function renderPagination() {
    var box = document.getElementById('fbPagination');
    if (totalPages <= 1) { box.innerHTML = ''; return; }
    var h = '<div class="pagination mt-24">';
    h += '<a href="javascript:loadFeedbackList(' + (currentPage-1) + ')" class="page-btn ' + (currentPage<=1?'disabled':'') + '">上一页</a>';
    var s = Math.max(1, currentPage-2), e = Math.min(totalPages, s+4); s = Math.max(1, e-4);
    for(var i=s; i<=e; i++) {
        h += '<a href="javascript:loadFeedbackList(' + i + ')" class="page-btn ' + (i===currentPage?'active':'') + '">' + i + '</a>';
    }
    h += '<a href="javascript:loadFeedbackList(' + (currentPage+1) + ')" class="page-btn ' + (currentPage>=totalPages?'disabled':'') + '">下一页</a>';
    h += '</div>';
    box.innerHTML = h;
}

function escapeHtml(s) {
    return String(s||'').replace(/[&<>"']/g, function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});
}
function timeAgo(t) {
    if (!t) return '';
    var d = new Date(t.replace(' ', 'T'));
    if (isNaN(d.getTime())) return t;
    var diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return Math.floor(diff) + '秒前';
    if (diff < 3600) return Math.floor(diff/60) + '分钟前';
    if (diff < 86400) return Math.floor(diff/3600) + '小时前';
    if (diff < 2592000) return Math.floor(diff/86400) + '天前';
    return t.slice(0, 10);
}

function submitFeedback(e) {
    e.preventDefault();
    <?php if (!$user): ?> alert('请先登录'); return; <?php endif; ?>
    var err = document.getElementById('fbFormError');
    err.style.display = 'none';
    var fd = new FormData(e.target);
    fetch('/api/feedback_api.php', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(res=>{
            if(res.code===0){
                e.target.reset();
                loadFeedbackList(1);
                alert('提交成功，感谢您的反馈！');
            }else{
                err.textContent = res.message;
                err.style.display = 'block';
            }
        });
}

function toggleLike(id, el) {
    <?php if (!$user): ?> location.href='/login.php'; return; <?php endif; ?>
    fetch('/api/feedback_api.php?like=1', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'feedback_id=' + id
    }).then(r=>r.json()).then(res=>{
        if(res.code===0){
            var icon = el.querySelector('.icon');
            var cnt = el.querySelector('.like-count');
            var num = parseInt(cnt.textContent || '0');
            if (res.data.liked) {
                el.classList.add('liked');
                icon.classList.add('active');
                cnt.textContent = num + 1;
            } else {
                el.classList.remove('liked');
                icon.classList.remove('active');
                cnt.textContent = Math.max(0, num - 1);
            }
        }
    });
}

function toggleReplyForm(id, el) {
    <?php if (!$user): ?> location.href='/login.php'; return; <?php endif; ?>
    var f = document.getElementById('replyForm-' + id);
    f.style.display = (f.style.display === 'flex') ? 'none' : 'flex';
    if (f.style.display === 'flex') setTimeout(function(){f.querySelector('input').focus();}, 100);
}

function submitReply(id) {
    var input = document.getElementById('replyInput-' + id);
    if (!input.value.trim()) return;
    fetch('/api/feedback_api.php?reply=1', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'feedback_id=' + id + '&content=' + encodeURIComponent(input.value)
    }).then(r=>r.json()).then(res=>{
        if(res.code===0){
            loadFeedbackList(currentPage);
        }else{
            alert(res.message);
        }
    });
}

function toggleCollapsed(id, el) {
    var box = document.getElementById('collapsed-' + id);
    box.classList.toggle('open');
    if (box.classList.contains('open')) {
        el.textContent = '收起回复';
    } else {
        el.textContent = '展开全部回复';
    }
}

// 初始加载
loadFeedbackList(1);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
