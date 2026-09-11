window.toggleNotif = function () {
    const menu = document.getElementById('notifDropdown');
    if (!menu) return;

    if (menu.style.display === 'block') {
        menu.style.display = 'none';
        return;
    }

    menu.style.display = 'block';
    const userMenu = document.getElementById('userDropdown');
    if (userMenu) userMenu.style.display = 'none';
};

window.toggleUserMenu = function () {
    const menu = document.getElementById('userDropdown');
    if (!menu) return;

    if (menu.style.display === 'block') {
        menu.style.display = 'none';
        return;
    }

    menu.style.display = 'block';
    const notifMenu = document.getElementById('notifDropdown');
    if (notifMenu) notifMenu.style.display = 'none';
};

window.handleNotificationKey = function (event, el) {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        window.openNotificationModal(el);
    }
};

function getNotificationField(source, key) {
    if (!source) return '';

    const camel = key.replace(/-([a-z])/g, function (_, c) {
        return c.toUpperCase();
    });

    if (source instanceof Element) {
        return source.dataset[camel] || source.getAttribute('data-' + key) || '';
    }

    return source[camel] || source[key] || '';
}

function getInitials(name) {
    const trimmed = String(name || '').trim();

    if (trimmed === '') {
        return 'SY';
    }

    return trimmed
        .split(/\s+/)
        .slice(0, 2)
        .map(function (part) {
            return part.charAt(0).toUpperCase();
        })
        .join('');
}

function getAttachmentName(attachment, filePath) {
    return String(attachment.original_name || attachment.originalName || attachment.name || filePath || 'Attachment');
}

function getAttachmentExtension(fileName) {
    const parts = String(fileName || '').toLowerCase().split('.');
    return parts.length > 1 ? parts.pop() : '';
}

function getAttachmentKind(attachment, filePath, fileName) {
    const mime = String(attachment.mime_type || attachment.mimeType || '').toLowerCase();
    const ext = getAttachmentExtension(fileName || filePath);

    if (mime.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(ext)) {
        return 'image';
    }

    if (mime === 'application/pdf' || ext === 'pdf') {
        return 'pdf';
    }

    return 'document';
}

function formatAttachmentSize(bytes) {
    const size = Number(bytes || 0);

    if (!Number.isFinite(size) || size <= 0) {
        return '';
    }

    if (size < 1024) {
        return `${size} B`;
    }

    const kilobytes = size / 1024;
    if (kilobytes < 1024) {
        return `${kilobytes.toFixed(kilobytes >= 10 ? 0 : 1)} KB`;
    }

    return `${(kilobytes / 1024).toFixed(1)} MB`;
}

function getAttachmentIconSvg(kind) {
    if (kind === 'image') {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2" ry="2"/><circle cx="9" cy="10" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';
    }

    if (kind === 'pdf') {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/><path d="M8 14h8"/><path d="M8 18h5"/></svg>';
    }

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"/><path d="M14 2v5h5"/><path d="M8 13h8"/><path d="M8 17h8"/></svg>';
}

function getAttachmentMetaLabel(kind, mime, sizeText) {
    const typeLabel = kind === 'image' ? 'Image' : (kind === 'pdf' ? 'PDF document' : 'Document');
    if (sizeText) {
        return `${typeLabel} · ${sizeText}`;
    }

    if (mime) {
        return `${typeLabel} · ${mime}`;
    }

    return typeLabel;
}

window.closeAttachmentPreviewModal = function () {
    const modal = document.getElementById('attachmentPreviewModal');
    const stage = document.getElementById('attachmentPreviewStage');

    if (stage) {
        stage.innerHTML = '';
    }

    if (modal) {
        modal.style.display = 'none';
    }
};

window.openAttachmentPreviewModal = function (attachment) {
    const modal = document.getElementById('attachmentPreviewModal');
    const stage = document.getElementById('attachmentPreviewStage');
    const nameNode = document.getElementById('attachmentPreviewName');
    const metaNode = document.getElementById('attachmentPreviewMeta');
    const downloadNode = document.getElementById('attachmentPreviewDownload');

    if (!modal || !stage || !nameNode || !metaNode || !downloadNode) {
        return;
    }

    const fileUrl = String(attachment && attachment.url ? attachment.url : '');
    const fileName = String(attachment && attachment.name ? attachment.name : 'Attachment');
    const mime = String(attachment && attachment.mime ? attachment.mime : '').toLowerCase();
    const kind = String(attachment && attachment.kind ? attachment.kind : 'document');

    nameNode.textContent = fileName;
    metaNode.textContent = getAttachmentMetaLabel(kind, mime, attachment && attachment.sizeText ? attachment.sizeText : '');
    downloadNode.href = fileUrl || '#';
    downloadNode.download = fileName;
    downloadNode.setAttribute('aria-label', `Download ${fileName}`);

    stage.innerHTML = '';

    if (kind === 'image') {
        const img = document.createElement('img');
        img.src = fileUrl;
        img.alt = fileName;
        stage.appendChild(img);
    } else if (kind === 'pdf' && fileUrl) {
        const iframe = document.createElement('iframe');
        iframe.src = fileUrl;
        iframe.title = fileName;
        iframe.loading = 'lazy';
        iframe.referrerPolicy = 'no-referrer';
        stage.appendChild(iframe);
    } else {
        const fallback = document.createElement('div');
        fallback.className = 'attachment-preview-fallback';
        fallback.innerHTML = `
            <div class="attachment-preview-fallback-icon">${getAttachmentIconSvg('document')}</div>
            <div class="attachment-preview-fallback-title">Preview unavailable for this file type</div>
            <div class="attachment-preview-fallback-text">Use the Download button to open the file locally.</div>
        `;
        stage.appendChild(fallback);
    }

    modal.style.display = 'flex';
};

window.openNotificationModal = function (el) {
    if (!el) return;

    const notifDropdown = document.getElementById('notifDropdown');
    if (notifDropdown) notifDropdown.style.display = 'none';

    const source = String(el.dataset.source || 'announcement');
    const notificationId = Number(el.dataset.id || 0);

    if (source === 'request') {
        const modal = document.getElementById('viewRequestNotificationModal');
        if (!modal) return;

        const titleNode = document.getElementById('viewRequestNotificationTitle');
        const requesterNameNode = document.getElementById('viewRequestAuthorName');
        const requesterAvatarNode = document.getElementById('viewRequestAuthorAvatar');
        const requesterSubtitleNode = modal.querySelector('.request-author-subtitle');
        const approvedByNode = document.getElementById('viewRequestApprovedBy');
        const approvedAvatarNode = document.getElementById('viewRequestApprovedAvatar');
        const approvalRoleNode = document.getElementById('viewRequestApprovalRole');
        const statusBadge = document.getElementById('viewRequestStatusBadge');
        const summaryNode = document.getElementById('viewRequestSummary');
        const detailsNode = document.getElementById('viewRequestDetails');
        const viewLink = document.getElementById('viewRequestLink');

        const requesterName = getNotificationField(el, 'requester-name') || getNotificationField(el, 'author-name') || 'System';
        const requesterDepartment = getNotificationField(el, 'requester-department') || '';
        const requesterPhoto = getNotificationField(el, 'requester-photo') || getNotificationField(el, 'author-photo') || '';
        const requesterInitials = getInitials(requesterName);

        modal.dataset.notificationId = String(el.dataset.id || '');
        modal.dataset.notificationSource = 'request';

        if (titleNode) titleNode.innerText = getNotificationField(el, 'title') || 'Request Notification';
        if (requesterNameNode) requesterNameNode.innerText = requesterName;
        if (requesterAvatarNode) {
            requesterAvatarNode.textContent = requesterPhoto ? '' : (requesterInitials || 'SY');
            requesterAvatarNode.style.backgroundImage = requesterPhoto ? `url("${requesterPhoto}")` : 'none';
            requesterAvatarNode.style.backgroundSize = requesterPhoto ? 'cover' : 'auto';
            requesterAvatarNode.style.backgroundPosition = 'center';
            requesterAvatarNode.style.backgroundRepeat = 'no-repeat';
            requesterAvatarNode.style.backgroundColor = requesterPhoto ? 'transparent' : '#ef4444';
        }
        if (requesterSubtitleNode) requesterSubtitleNode.innerText = requesterDepartment || '';

        if (approvedByNode) approvedByNode.innerText = getNotificationField(el, 'actor-name') || getNotificationField(el, 'author-name') || 'System';
        if (approvedAvatarNode) {
            const actorName = (getNotificationField(el, 'actor-name') || getNotificationField(el, 'author-name') || 'System').trim();
            approvedAvatarNode.textContent = getInitials(actorName);
        }
        if (approvalRoleNode) {
            const actorPosition = String(getNotificationField(el, 'actor-position') || '').trim();
            approvalRoleNode.innerText = actorPosition !== ''
                ? actorPosition
                : ((getNotificationField(el, 'status') || '').toLowerCase() === 'rejected' ? 'Reviewer' : 'Approver');
        }

        if (statusBadge) {
            statusBadge.classList.remove('is-neutral', 'is-approved', 'is-rejected');
            const statusVal = String(getNotificationField(el, 'status') || '').toLowerCase();
            let label = getNotificationField(el, 'status') || getNotificationField(el, 'title') || 'Pending';
            let stateClass = 'is-neutral';

            if (statusVal === 'approved' || statusVal === 'approve') {
                label = 'Approved Request';
                stateClass = 'is-approved';
            } else if (statusVal === 'rejected' || statusVal === 'deny') {
                label = 'Rejected Request';
                stateClass = 'is-rejected';
            } else if (statusVal === 'pending') {
                label = 'Pending';
            }

            statusBadge.innerText = label;
            statusBadge.classList.add(stateClass);
        }

        if (summaryNode) summaryNode.innerText = getNotificationField(el, 'summary') || '';
        if (detailsNode) detailsNode.innerText = getNotificationField(el, 'details') || getNotificationField(el, 'summary') || '';

        const requestTypeNode = document.getElementById('viewRequestType');
        const processedByNode = document.getElementById('viewRequestProcessedBy');
        const submittedAtNode = document.getElementById('viewRequestSubmittedAt');
        const refNode = document.getElementById('viewRequestRef');

        if (requestTypeNode) {
            let rType = String(getNotificationField(el, 'request-type') || getNotificationField(el, 'category') || 'Request');
            rType = rType.trim();
            if (rType.length <= 2) {
                rType = rType.toUpperCase();
            } else if (rType !== '') {
                rType = rType.charAt(0).toUpperCase() + rType.slice(1);
            }
            requestTypeNode.innerText = rType;
        }
        if (processedByNode) processedByNode.innerText = getNotificationField(el, 'actor-name') || getNotificationField(el, 'processed-by') || 'System';
        if (submittedAtNode) submittedAtNode.innerText = getNotificationField(el, 'publish') || getNotificationField(el, 'publish-at') || 'N/A';
        if (refNode) refNode.innerText = getNotificationField(el, 'ref') || ('#' + (getNotificationField(el, 'request-id') || getNotificationField(el, 'id') || '0'));

        if (viewLink) {
            const requestId = String(getNotificationField(el, 'request-id') || getNotificationField(el, 'id') || '');
            const baseUrl = window.requestsListUrl || '/client_portal/requests';
            viewLink.href = getNotificationField(el, 'request-url') || (requestId ? `${baseUrl}#request-${requestId}` : baseUrl);
        }

        if (notificationId && !el.classList.contains('header-notif-viewed') && !el.classList.contains('notif-viewed')) {
            window.syncNotificationViewedState(notificationId, true, 'request');
            window.markNotificationAsViewed(notificationId);
        }

        modal.style.display = 'flex';
        return;
    }

    const modal = document.getElementById('viewNotificationsModal');
    if (!modal) return console.error('Notification modal not found');

    const title = el.dataset.title || 'Notification';
    const summary = el.dataset.summary || '';
    const details = el.dataset.details || summary;
    const titleNode = document.getElementById('viewNotificationTitle');
    const publishedNode = document.getElementById('viewNotificationPublished');
    const categoryNode = document.getElementById('viewNotificationCategory');
    const priorityNode = document.getElementById('viewNotificationPriority');
    const audienceNode = document.getElementById('viewNotificationAudience');
    const authorNameNode = document.getElementById('viewNotificationAuthorName');
    const authorAvatarNode = document.getElementById('viewNotificationAuthorAvatar');
    const summaryNode = document.getElementById('viewNotificationSummary');
    const detailsNode = document.getElementById('viewNotificationDetails');
    const attachmentsCountNode = document.getElementById('viewNotificationAttachmentsCount');

    modal.dataset.notificationId = String(el.dataset.id || '');
    modal.dataset.notificationSource = String(el.dataset.source || 'announcement');
    modal.dataset.notificationCreatedBy = String(el.dataset.createdBy || '0');

    const editBtn = document.getElementById('notificationEditBtn');
    if (editBtn) {
        const canEdit = modal.dataset.notificationSource === 'announcement' && !!window.canEditAnnouncements;
        editBtn.style.display = canEdit ? '' : 'none';
    }

    const authorName = el.dataset.authorName || 'System';
    const authorPhoto = el.dataset.authorPhoto || '';
    const authorInitials = authorName.trim() !== ''
        ? authorName.trim().split(/\s+/).slice(0, 2).map(function (part) {
            return part.charAt(0).toUpperCase();
        }).join('')
        : 'SY';

    if (titleNode) titleNode.innerText = title;
    if (publishedNode) publishedNode.innerText = el.dataset.publish || 'N/A';
    if (categoryNode) categoryNode.innerText = el.dataset.category || 'General';
    if (priorityNode) priorityNode.innerText = el.dataset.priority || 'Normal';
    if (audienceNode) audienceNode.innerText = el.dataset.audience || 'All Employees';
    if (authorNameNode) authorNameNode.innerText = authorName;
    if (authorAvatarNode) {
        authorAvatarNode.textContent = authorPhoto ? '' : (authorInitials || 'SY');
        authorAvatarNode.style.backgroundImage = authorPhoto ? `url("${authorPhoto}")` : 'none';
        authorAvatarNode.style.backgroundSize = authorPhoto ? 'cover' : 'auto';
        authorAvatarNode.style.backgroundPosition = 'center';
        authorAvatarNode.style.backgroundRepeat = 'no-repeat';
        authorAvatarNode.style.backgroundColor = authorPhoto ? 'transparent' : '#ef4444';
    }
    if (summaryNode) summaryNode.innerText = summary;
    if (detailsNode) detailsNode.innerText = details;

    // Attachments rendering
    try {
        const attachmentsRaw = el.dataset.attachments || '';
        const attachmentsWrap = document.getElementById('viewNotificationAttachmentsWrap');
        const attachmentsContainer = document.getElementById('viewNotificationAttachments');
        if (attachmentsContainer) attachmentsContainer.innerHTML = '';

        if (attachmentsRaw) {
            let attachments = [];
            try { attachments = JSON.parse(attachmentsRaw); } catch (e) { attachments = []; }

            if (Array.isArray(attachments) && attachments.length > 0) {
                if (attachmentsWrap) attachmentsWrap.style.display = '';
                if (attachmentsCountNode) {
                    attachmentsCountNode.textContent = `${attachments.length} file${attachments.length === 1 ? '' : 's'}`;
                }
                attachments.forEach(function (att, index) {
                    const fp = att.file_path || att.filePath || att.path || '';
                    const name = getAttachmentName(att, fp);
                    const mime = String(att.mime_type || att.mimeType || '').toLowerCase();
                    const sizeText = formatAttachmentSize(att.file_size || att.fileSize || 0);
                    const kind = getAttachmentKind(att, fp, name);

                    const card = document.createElement('div');
                    card.className = 'attachment-card';

                    const openBtn = document.createElement('button');
                    openBtn.type = 'button';
                    openBtn.className = `attachment-open-btn attachment-kind-${kind}`;
                    openBtn.setAttribute('aria-label', `Preview ${name}`);
                    openBtn.title = `Preview ${name}`;
                    openBtn.innerHTML = getAttachmentIconSvg(kind);
                    openBtn.addEventListener('click', function () {
                        window.openAttachmentPreviewModal({
                            url: fp,
                            name: name,
                            mime: mime,
                            kind: kind,
                            sizeText: sizeText,
                        });
                    });

                    const details = document.createElement('div');
                    details.className = 'attachment-details';

                    const fileName = document.createElement('div');
                    fileName.className = 'attachment-name';
                    fileName.textContent = name;

                    const fileMeta = document.createElement('div');
                    fileMeta.className = 'attachment-size';
                    fileMeta.textContent = getAttachmentMetaLabel(kind, mime, sizeText);

                    details.appendChild(fileName);
                    details.appendChild(fileMeta);

                    const downloadBtn = document.createElement('a');
                    downloadBtn.className = 'attachment-download';
                    downloadBtn.href = fp || '#';
                    downloadBtn.download = name;
                    downloadBtn.setAttribute('aria-label', `Download ${name}`);
                    downloadBtn.title = `Download ${name}`;
                    downloadBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>';
                    downloadBtn.addEventListener('click', function (event) {
                        event.stopPropagation();
                    });

                    card.appendChild(openBtn);
                    card.appendChild(details);
                    card.appendChild(downloadBtn);

                    if (attachmentsContainer) attachmentsContainer.appendChild(card);
                });
            } else {
                if (attachmentsWrap) attachmentsWrap.style.display = 'none';
            }
        } else {
            if (attachmentsWrap) attachmentsWrap.style.display = 'none';
        }
    } catch (err) {
        console.error('Failed to render attachments', err);
    }

    if (notificationId && !el.classList.contains('header-notif-viewed') && !el.classList.contains('notif-viewed')) {
        window.syncNotificationViewedState(notificationId, true, modal.dataset.notificationSource || 'announcement');
        window.markNotificationAsViewed(notificationId);
    }

    modal.style.display = 'flex';
};

window.updateHeaderNotifBadge = function () {
    const badge = document.querySelector('.header-notif-badge');
    const unread = document.querySelectorAll('.header-notif-item:not(.header-notif-viewed)').length;

    if (unread <= 0) {
        if (badge) badge.remove();
        return;
    }

    if (badge) {
        badge.textContent = String(unread);
        return;
    }

    const btn = document.querySelector('.header-notif-btn');
    if (!btn) return;
    const newBadge = document.createElement('span');
    newBadge.className = 'header-notif-badge';
    newBadge.textContent = String(unread);
    btn.appendChild(newBadge);
};

window.syncNotificationViewedState = function (notificationId, isViewed, source = 'announcement') {
    if (!notificationId) return;

    document.querySelectorAll(`.header-notif-item[data-id="${notificationId}"][data-source="${source}"], .notif[data-id="${notificationId}"][data-source="${source}"]`).forEach(function (item) {
        item.classList.toggle('header-notif-viewed', isViewed);
        item.classList.toggle('notif-viewed', isViewed);
    });

    window.updateHeaderNotifBadge();
};

window.refreshNotificationGroupState = function () {
    const groups = [
        {
            group: document.getElementById('publishedGroup'),
            badge: document.querySelector('#tabPublished .notifications-badge'),
        },
        {
            group: document.getElementById('unpublishedGroup'),
            badge: document.querySelector('#tabUnpublished .notifications-badge'),
        },
    ];

    groups.forEach(function (entry) {
        if (!entry.group) {
            return;
        }

        entry.group.querySelectorAll('.notifications-section').forEach(function (section) {
            if (!section.querySelector('.notif')) {
                section.remove();
            }
        });

        const remainingItems = entry.group.querySelectorAll('.notif').length;

        if (entry.badge) {
            entry.badge.textContent = String(remainingItems);
        }

        if (remainingItems === 0) {
            entry.group.innerHTML = '<div class="notifications-empty">No notifications</div>';
        }
    });
};

window.refreshHeaderNotificationState = function () {
    const notifList = document.querySelector('.header-notif-list');
    if (!notifList) {
        return;
    }

    const headerItems = notifList.querySelectorAll('.header-notif-item');
    const remaining = headerItems.length;

    window.updateHeaderNotifBadge();

    const sectionLabels = notifList.querySelectorAll('.header-notif-section-label');
    const existingEmpty = notifList.querySelector('.header-notif-empty');

    if (remaining === 0) {
        sectionLabels.forEach(function (el) {
            el.remove();
        });

        headerItems.forEach(function (item) {
            item.remove();
        });

        if (!existingEmpty) {
            const empty = document.createElement('div');
            empty.className = 'header-notif-empty';
            empty.textContent = 'No notifications';
            notifList.appendChild(empty);
        }
        return;
    }

    if (existingEmpty) {
        existingEmpty.remove();
    }

    if (!sectionLabels.length) {
        const label = document.createElement('div');
        label.className = 'header-notif-section-label';
        label.textContent = 'Today';
        notifList.insertBefore(label, notifList.firstChild);
    }
};

async function sendNotificationViewed(notificationId, source) {
    const viewedUrl = source === 'request' ? window.requestNotificationViewedUrl : window.notificationViewedUrl;

    if (!notificationId || !viewedUrl || !window.notificationCsrfTokenName || !window.notificationCsrfTokenHash) {
        return false;
    }

    const payload = new URLSearchParams();
    payload.append(source === 'request' ? 'notification_id' : 'announcement_id', String(notificationId));
    payload.append(window.notificationCsrfTokenName, window.notificationCsrfTokenHash);

    try {
        const response = await fetch(viewedUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: payload.toString(),
        });

        const result = await response.json();

        if (result && result.csrfHash) {
            window.notificationCsrfTokenHash = result.csrfHash;
        }

        return response.ok && result && result.success === true;
    } catch (error) {
        console.error('Unable to mark notification as viewed.', error);
        return false;
    }
}

window.markNotificationAsViewed = async function (notificationId) {
    let modal = document.getElementById('viewNotificationsModal');
    if (!(modal?.style?.display === 'flex' || modal?.dataset?.notificationId)) {
        modal = document.getElementById('viewRequestNotificationModal') || modal;
    }

    const source = modal?.dataset?.notificationSource || 'announcement';

    const ok = await sendNotificationViewed(notificationId, source);
    if (!ok) {
        window.syncNotificationViewedState(notificationId, false, source);
    }
};

window.markAllNotificationsAsRead = async function () {
    const unreadItems = Array.from(document.querySelectorAll('.header-notif-item:not(.header-notif-viewed)'));

    if (unreadItems.length === 0) {
        return;
    }

    const results = await Promise.all(unreadItems.map(function (item) {
        const notificationId = Number(item.dataset.id || 0);
        const source = String(item.dataset.source || 'announcement');

        return sendNotificationViewed(notificationId, source).then(function (ok) {
            return { item: item, notificationId: notificationId, source: source, ok: ok };
        });
    }));

    results.forEach(function (result) {
        if (result.ok) {
            window.syncNotificationViewedState(result.notificationId, true, result.source);
        }
    });
};

window.deleteCurrentNotificationForMe = async function () {
    let modal = document.getElementById('viewNotificationsModal');
    if (!(modal?.style?.display === 'flex' || modal?.dataset?.notificationId)) {
        modal = document.getElementById('viewRequestNotificationModal') || modal;
    }

    const notificationId = Number(modal?.dataset.notificationId || 0);
    const source = modal?.dataset.notificationSource || 'announcement';
    const dismissedUrl = source === 'request' ? window.requestNotificationDismissedUrl : window.notificationDismissedUrl;

    if (!notificationId || !dismissedUrl || !window.notificationCsrfTokenName || !window.notificationCsrfTokenHash) {
        return;
    }

    const payload = new URLSearchParams();
    payload.append(source === 'request' ? 'notification_id' : 'announcement_id', String(notificationId));
    payload.append(window.notificationCsrfTokenName, window.notificationCsrfTokenHash);

    try {
        const response = await fetch(dismissedUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: payload.toString(),
        });

        const result = await response.json();

        if (result && result.csrfHash) {
            window.notificationCsrfTokenHash = result.csrfHash;
        }

        if (!response.ok || !result || result.success !== true) {
            showToast(result?.message || 'Unable to delete notification.', 'error');
            return;
        }

        if (modal) modal.style.display = 'none';
        window.syncNotificationViewedState(notificationId, false, source);

        const selector = source === 'request'
            ? `.header-notif-item[data-id="${notificationId}"][data-source="request"], .notif[data-id="${notificationId}"][data-source="request"]`
            : `.header-notif-item[data-id="${notificationId}"][data-source="announcement"], .notif[data-id="${notificationId}"][data-source="announcement"]`;

        // Remove matching notification elements
        document.querySelectorAll(selector).forEach(function (item) {
            item.remove();
        });

        window.refreshNotificationGroupState();
        window.refreshHeaderNotificationState();

        // Update header badge count (if present)
        try {
            window.refreshHeaderNotificationState();
        } catch (err) {
            console.warn('Failed to update notification header state', err);
        }
    } catch (error) {
        console.error('Unable to delete notification.', error);
        showToast('Unable to delete notification.', 'error');
    }
};

window.editCurrentNotificationHandler = function () {
    const modal = document.getElementById('viewNotificationsModal');
    const notificationId = Number(modal?.dataset?.notificationId || 0);
    const source = modal?.dataset?.notificationSource || 'announcement';

    if (!notificationId || source !== 'announcement') {
        showToast('This notification cannot be edited.', 'info');
        return;
    }

    if (typeof window.openAnnouncementEditModal === 'function') {
        window.closeNotificationModal();
        window.openAnnouncementEditModal(notificationId);
        return;
    }

    const dashboardUrl = window.dashboardUrl || '/client_portal/dashboard';
    window.location.href = `${dashboardUrl}?edit_announcement=${notificationId}`;
};

window.closeNotificationModal = function () {
    const announcementModal = document.getElementById('viewNotificationsModal');
    const requestModal = document.getElementById('viewRequestNotificationModal');
    if (announcementModal) announcementModal.style.display = 'none';
    if (requestModal) requestModal.style.display = 'none';
};

document.addEventListener('click', function (event) {
    const notifWrapper = document.querySelector('.header-notif-wrapper');
    const userWrapper = document.querySelector('.user-wrapper');

    if (notifWrapper && !notifWrapper.contains(event.target)) {
        const notifDropdown = document.getElementById('notifDropdown');
        if (notifDropdown) notifDropdown.style.display = 'none';
    }

    if (userWrapper && !userWrapper.contains(event.target)) {
        const userDropdown = document.getElementById('userDropdown');
        if (userDropdown) userDropdown.style.display = 'none';
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        const notifDropdown = document.getElementById('notifDropdown');
        const userDropdown = document.getElementById('userDropdown');
        if (notifDropdown) notifDropdown.style.display = 'none';
        if (userDropdown) userDropdown.style.display = 'none';
    }
});
