<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$user = auth()->user();
$currentAttendance = $currentAttendance ?? null;
?>

<style>
    body{
        background:#f3f4f6;
        font-family:Segoe UI, Arial, sans-serif;
        margin:0;
    }

    /* CENTER CONTENT WIDTH */
    .dashboard{
        display: flex;
        flex-direction: column;
        align-items: stretch;
        padding: 0 2rem 2rem 2rem;
        width: 100%;
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        box-sizing: border-box;
    }

    /* LIMIT CONTENT WIDTH */
    .header,
    .dashboard-main{
        width:100%;
        max-width:none;
        height:auto;
    }

    /* HEADER */
    .header{
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        margin-bottom: 1rem;
    }

    /* TITLE */
    .header h1{
        font-size:clamp(20px, 2.5vw, 28px);
        font-weight:700;
        margin:0;
    }

    /* BUTTON */
    .request-btn{
        background: #ef4444;
        color: white;
        padding: 10px 18px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }

    .request-btn:hover{
        background: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
    }

    /* MAIN LAYOUT */
    .dashboard-main{
        display: grid;
        grid-template-columns: minmax(0, 1fr) clamp(400px, 34vw, 500px);
        align-items: stretch;
        gap: 25px;
        width: 100%;
        flex: 1;
        min-height: 0;
    }

    /* CLOCK CARD */
    .clock-card{
        min-width: 0;
        max-width: none;
        background: white;
        display: flex;
        flex-direction: column;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
        justify-content: flex-start;
        height:100%;
    }

    /* TITLE */
    .clock-title{
        text-align: center;
        color: #ef4444;
        font-weight: 700;
        margin-bottom: 10px;
        font-size: clamp(20px, 2vw, 28px);
    }

    /* INNER BOX */
    .clock-inner{
        display:flex;
        flex-direction:column;
        justify-content:center;
        align-items:center;
        background:#e5e7eb;
        border-radius:12px;
        padding:25px;
        margin-bottom:10px;
        text-align:center;
        flex:1;
        min-height:320px;
    }

    /* STATUS */
    .status{
        font-size: 18px;
        color:#6b7280;
        font-weight:500;
        padding: 18px;
    }

    .status span{
        background:#bbf7d0;
        color:#065f46;
        padding:4px 12px;
        border-radius:20px;
        font-size:13px;
    }

    /* CLOCK */
    .clock{
        width:clamp(180px, 25vw, 240px);
        height:clamp(180px, 25vw, 240px);
        position:relative;
        display:flex;
        align-items:center;
        justify-content:center;
        padding: 20px;
    }

    .clock svg{
        position:absolute;
        width:100%;
        height:100%;
        transform:rotate(-90deg);
    }

    .shift-time{
        position:absolute;
        font-size:clamp(28px, 4vw, 40px);
        font-weight:700;
    }

    /* DATE */
    .date{
        font-size: 18px;
        color: #4b5563;
        font-weight: 500;
        margin-top: 18px;
    }

    /* BUTTONS */
    .buttons{
        display:flex;
        gap:12px;
        align-items:center;
        width:100%;
        margin-top:12px;
    }

    /* Only the Clock In/Out form should stretch to fill the row — its button
       is width:100% so it uses that space. The break-toggle form wraps just a
       fixed 48px icon button, so if it also stretched (the old plain "form"
       selector matched both), it left a dead gap before the task-log icon:
       the form's box grew but the button inside stayed small and left-aligned. */
    .buttons form.clock-form{
        flex:1;
        margin:0;
    }
    .buttons form.break-form{
        flex:0 0 auto;
        margin:0;
    }

    .clockout{
        width:100%;
        height:48px;
        background:#ef4444;
        border:none;
        color:white;
        border-radius:12px;
        font-weight:600;
        cursor:pointer;
        box-shadow:0 2px 6px rgba(0,0,0,0.15);
        display:block;
        transition:all 0.2s ease;
    }

    .clockout:hover{
        background:#dc2626;
        transform:translateY(-2px);
        box-shadow:0 6px 12px rgba(239, 68, 68, 0.3);
    }

    /* ICON BUTTON */
    .icon-btn{
        width:48px;
        height:48px;
        min-width:48px;
        border:none;
        border-radius:12px;
        background:#f3f4f6;
        background-repeat:no-repeat;
        background-position:center;
        background-size:22px;
        cursor:pointer;
        display:flex;
        align-items:center;
        justify-content:center;
        transition:all 0.2s ease;
    }

    .icon-btn:hover{
        background-color:#e5e7eb;
        transform:scale(1.08);
    }

    /* ICONS */
    .coffee{
        background-image:url("<?= base_url('icons/coffee.png') ?>");
    }

    .idcard{
        background-image:url("<?= base_url('icons/add_notes.png') ?>");
    }

    .on-break{
        background-color:#fb923c !important;
    }


    /* NOTIFICATIONS */
    .notifications{
        width:100%;
        max-width:none;
        background: linear-gradient(180deg, #ffffff 0%, #fcfcfe 100%);
        border-radius:18px;
        box-shadow:0 14px 30px rgba(17, 24, 39, 0.10);
        border:1px solid #ececec;
        display:flex;
        flex-direction:column;
        overflow:hidden;
        max-height:none;
        height:100%;
        min-height:0;
    }

    .notifications-divider{
        border:0;
        border-top:1px solid #e5e7eb;
    }

    /* NOTIFICATION TITLE */
    .notifications-title{
        color:#232323;
        font-size:24px;
        font-weight:700;
        letter-spacing:-0.015em;
    }

    .notifications-top{
        display:flex;
        align-items:center;
        justify-content:space-between;
        margin: 20px 20px 14px;
    }

    .notifications-clear{
        border:none;
        background:transparent;
        color:#4b5563;
        font-size:12px;
        font-weight:600;
        cursor:pointer;
        padding:6px 8px;
        border-radius:8px;
        display:flex;
        align-items:center;
        gap:4px;
    }

    .notifications-clear .dot{
        width:25px;
        height:25px;
    }

    .notifications-clear:hover{
        color:#1f2937;
        background:#f3f4f6;
    }

    .notifications-tabs{
        display:flex;
        align-items:center;
        gap:10px;
        margin: 12px 20px 15px;
    }

    .notifications-tab{
        border:1px solid transparent;
        background:#f3f4f6;
        font-size:12px;
        font-weight:600;
        color:#4b5563;
        border-radius:999px;
        padding:8px 13px;
        cursor:pointer;
        display:flex;
        align-items:center;
        gap:7px;
        transition:all 0.2s ease;
    }

    .notifications-tab:hover{
        background:#ede9fe;
        color:#1f2937;
    }

    .notifications-tab.active{
        background:#EBE5FF;
        color:#1f2937;
        border-color:rgba(126, 35, 253, 0.25);
        box-shadow:0 2px 8px rgba(126, 35, 253, 0.14);
    }

    .notifications-badge{
        background:#7E23FD;
        color:#fff;
        font-size:11px;
        font-weight:700;
        border-radius:999px;
        padding:2px 8px;
        line-height:1.4;
    }

    .notifications-divider-title{
        display:flex;
        align-items:center;
        gap:8px;
        font-size:11px;
        font-weight:700;
        color:#6b7280;
        margin:10px 0 10px;
        text-transform:uppercase;
        letter-spacing:0.06em;
    }

    .notifications-section{
        display:flex;
        flex-direction:column;
        gap:10px;
    }

    .notifications-divider-title::after{
        content:'';
        flex:1;
        height:1px;
        background:#dedede;
    }

    .notifications-empty{
        font-size:12px;
        color:#7d7d7d;
        margin:10px 0 14px;
        text-align: center;
        min-height: 100%;
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .notif-meta{
        margin-top:6px;
        font-size:11px;
        color:#6b7280;
    }

    .notifications-group{
        display:none;
        padding: 0 18px 16px 20px;
        overflow-y:auto;
        flex:1;
        margin-bottom:14px; /* keep list content away from card bottom edge */
        min-height:0;
        box-sizing:border-box;
        scrollbar-gutter:stable;
    }

    .notifications-group.active{
        display: block;
    }

    /* .notifications-group::after{
        content:'';
        display:block;
        height:18px;
    } */

    /* NOTIFICATION ITEM */
    .notif{
        position:relative;
        display:flex;
        align-items:flex-start;
        gap:12px;
        padding:16px 18px 12px 18px;
        min-height: 110px;
        background:#ffffff;
        border-radius:14px;
        cursor:pointer;
        border:1px solid transparent;   
        box-shadow:0 6px 14px rgba(15, 23, 42, 0.09);
        transition:background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .notif:hover{
        background:#f6f2ff;
        transform:translateY(-2px);
        border-color:rgba(126, 35, 253, 0.2);
        box-shadow:0 12px 22px rgba(126, 35, 253, 0.20);
    }

    .notif::before{
        content:'';
        position:absolute;
        left:8px;
        top:18px;
        width:8px;
        height:8px;
        border-radius:50%;
        background:#7E23FD;
        transition:transform 0.2s ease, box-shadow 0.2s ease;
    }

    .notif:hover::before{
        transform:scale(1.2);
        box-shadow:0 0 0 4px rgba(126, 35, 253, 0.15);
    }

    .notif:hover::after{
        transform:translateY(-50%) translateX(2px);
        opacity:1;
    }

    .notif.notif-viewed::before{
        display:none;
    }

    .notif-avatar{
        width:40px;
        height:40px;
        border-radius:12px;
        display:flex;
        align-items:center;
        justify-content:center;
        background:linear-gradient(135deg, #7E23FD, #a855f7);
        color:#fff;
        font-weight:700;
        font-size:12px;
        letter-spacing:0.02em;
        flex-shrink:0;
    }

    .notif-content{
        flex:1;
        min-width:0;
        padding-top:1px;
    }

    .notif-title-row{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
    }

    .notif-time{
        font-size:12px;
        color:#6b7280;
        font-weight:500;
        white-space: nowrap;
        flex-shrink:0;
        margin-top:1px;
    }

    .notif-tags{
        display:flex;
        align-items:center;
        gap:6px;
        margin-top:8px;
    }

    .notif-pill{
        display:inline-flex;
        align-items:center;
        border-radius:999px;
        padding:2px 8px;
        font-size:10px;
        font-weight:700;
        letter-spacing:0.03em;
        text-transform:uppercase;
        background:#efe7ff;
        color:#5b21b6;
    }

    .notif-pill.priority-normal{
        background:#dbeafe;
        color:#1e40af;
    }

    .notif-pill.priority-high{
        background:#fed7aa;
        color:#b45309;
    }

    .notif-pill.priority-urgent{
        background:#fecaca;
        color:#991b1b;
    }

    .notif-pill.muted{
        background:#f3f4f6;
        color:#4b5563;
    }

    .notif-thumb{
        width:58px;
        height:58px;
        border-radius:14px;
        background:linear-gradient(140deg, #f4d03f, #16a085);
        position:relative;
        overflow:hidden;
        flex-shrink:0;
    }

    .notif-thumb::after{
        content:'+';
        position:absolute;
        right:4px;
        bottom:4px;
        width:18px;
        height:18px;
        border-radius:50%;
        background:#ef4444;
        color:#fff;
        font-size:12px;
        color:white;
        display:flex;
        align-items:center;
        justify-content:center;
        font-weight:700;
    }

    .notif-title{
        font-weight:700;
        font-size:16px;
        color:#262626;
        line-height:1.35;
    }

    .notif-desc{
        font-size:14px;
        color:#6b7280;
        margin-top:6px;
        line-height:1.5;
        display:-webkit-box;
        -webkit-line-clamp:1;
        -webkit-box-orient:vertical;
        overflow:hidden;
        text-overflow:ellipsis;
    }

    /* MODAL */
    .modal-overlay{
        position:fixed;
        top:0;
        left:0;
        width:100%;
        height:100%;
        background:rgba(0,0,0,0.45);
        display:none;
        align-items:center;
        justify-content:center;
        z-index:9999;
    }

    .modal{
        background:white;
        width:100%;
        max-width:460px;
        max-height:85vh;
        display:flex;
        flex-direction:column;
        overflow:hidden;
        border-radius:14px;
        box-shadow:0 25px 50px rgba(0,0,0,0.25);
        animation:modalPop 0.2s ease;
    }

    #taskLogModal .modal{
        max-width:640px;
    }

    #requestModal .modal form{
        display:flex;
        flex-direction:column;
        min-height:0;
        max-height:85vh;
        flex:1;
        overflow:hidden;
    }

    #viewNotificationsModal .modal {
        max-width: 640px;
    }

    .view-notification-body {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .view-notification-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px 14px;
        padding: 10px 12px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .view-notification-meta p {
        margin: 0;
        font-size: 12px;
        color: #475569;
    }

    .view-notification-meta strong {
        color: #0f172a;
    }

    .view-notification-content {
        margin: 0;
        font-size: 14px;
        line-height: 1.6;
        color: #334155;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .modal-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        font-size: 20px;
        font-weight: 700;
        padding:16px 20px;
        margin-bottom:0;
        flex-shrink:0;
        border-bottom:1px solid #e5e7eb;
    }

    .modal input,
    .modal select,
    .modal textarea{
        width:100%;
        padding:10px;
        border:1px solid #d1d5db;
        border-radius:8px;
        margin-top: 5px;
        margin-bottom:10px;
    }

    .modal textarea{
        height:80px;
        resize:none;
    }

    .modal-footer{
        display:flex;
        justify-content:flex-end;
        gap:10px;
        flex-shrink:0;
        padding:14px 20px;
        border-top:1px solid #e5e7eb;
    }

    .cancel-btn{
        border:none;
        background:#e5e7eb;
        padding:8px 14px;
        border-radius:6px;
        cursor:pointer;
        transition:all 0.2s ease;
    }

    .cancel-btn:hover{
        background:#d1d5db;
        transform:translateY(-2px);
    }

    .submit-btn{
        background:#8b5cf6;
        color:white;
        border:none;
        padding:8px 16px;
        border-radius:6px;
        cursor:pointer;
        transition:all 0.2s ease;
    }

    .submit-btn:hover{
        background:#7c3aed;
        transform:translateY(-2px);
        box-shadow:0 4px 12px rgba(139, 92, 246, 0.3);
    }

    .button-holder{
        display:flex;
        align-items:center;
        gap: 10px;
    }

    .btn-icon{
        width:26px;
        height:26px;
        object-fit:contain;
    }

    /* ANNOUNCEMENT MODAL (OWN STYLES) */
    .announcement-modal{
        max-width:920px;
        width:min(92vw, 920px);
        max-height:90vh;
        padding:0;
        overflow:hidden;
        border-radius:16px;
    }

    .announcement-form{
        display:flex;
        flex-direction:column;
        max-height:90vh;
    }

    .modal-body{
        flex:1;
        min-height:0;
        padding: 16px 20px;
        overflow-y:auto;
    }

    .announcement-grid{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap: 0px 15px;
    }

    .announcement-field{
        display:flex;
        flex-direction:column;
    }

    .announcement-field.full-span{
        grid-column:1 / -1;
    }

    .modal-body label{
        font-size:15px;
        color:#374151;
        font-weight:600;
    }

    .announcement-field input,
    .announcement-field select,
    .announcement-field textarea{
        width:100%;
        border:1px solid #d1d5db;
        border-radius:8px;
    }

    .announcement-field textarea{
        resize:vertical;
        min-height: 82px;
    }

    .announcement-counter{
        text-align:right;
        font-size:12px;
        padding: 0;
        color:#6b7280;
    }

    /* Attachments list for announcement modal */
    .announcement-attachments-list{
        margin-top:8px;
        display:flex;
        gap:8px;
        flex-wrap:wrap;
    }
    .attachment-item{
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:6px;
        width:110px;
    }
    .attachment-preview{
        width:110px;
        height:80px;
        border-radius:8px;
        background:#f8fafc;
        border:1px solid #e5e7eb;
        display:flex;
        align-items:center;
        justify-content:center;
        overflow:hidden;
        text-align:center;
        padding:6px;
    }
    .attachment-preview img{
        max-width:100%;
        max-height:100%;
        object-fit:cover;
    }

    /* file input + attach button */
    .file-input-wrapper{
        display:flex;
        align-items:center;
        gap:8px;
    }
    .file-upload-button{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        width:36px;
        height:36px;
        border-radius:8px;
        background:#eef2ff;
        border:1px solid #e5e7eb;
        cursor:pointer;
    }
    .file-upload-button:hover{ background:#e0e7ff; }
    .file-input-hidden{ /* keep native input but hide default UI */
        opacity:0;
        position:relative;
        width:1px;
        height:1px;
        overflow:hidden;
    }
    .announcement-attachments-name{
        font-size:13px;
        color:#6b7280;
    }

    .announcement-check{
        display:flex;
        align-items:center;
        gap:8px;
        font-size:13px;
        color:#4b5563;
    }

    .announcement-check input{
        width:auto;
        margin:0;
        padding:0;
        border:none;
        border-radius:0;
    }

    .checkbox-container{
        display:flex;
        align-items:center;
        gap:8px;
        font-size:13px;
        color:#4b5563;
        padding: 10px 0px;
    }

    .checkbox-container input[type="checkbox"]{
        width:15px;
        height:15px;
        cursor:pointer;
        margin:0;
        padding:0;
        accent-color:#8b5cf6;
    }

    .tl-duration-chip{
        border:1px solid #e5e7eb; background:#f9fafb; color:#4b5563;
        padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600;
        cursor:pointer; transition:.15s ease;
    }
    .tl-duration-chip:hover{ background:#ede9fe; border-color:#c4b5fd; color:#6d28d9; }

    /* Compound selectors (.modal .tl-time-select) so this reliably beats the
       generic ".modal select { width:100%; ... }" rule above regardless of
       load order — see the civm-* fix in components/view_modal.php for the
       same pattern applied to a different collision. */
    .modal .tl-time-row{ display:flex; align-items:center; gap:4px; margin-top:5px; margin-bottom:10px; }
    .modal .tl-time-select{
        flex:1; min-width:0; width:auto; margin:0; padding:8px 4px;
        border:1px solid #d1d5db; border-radius:8px; text-align:center; font-size:13px;
    }
    .modal .tl-time-select.tl-time-ampm{ flex:0 0 58px; }
    .modal .tl-time-colon{ color:#9ca3af; font-weight:700; font-size:13px; }

    @keyframes modalPop{
        from{ transform:scale(0.95); opacity:0; }
        to{ transform:scale(1); opacity:1; }
    }

    /* MOBILE */
    /* ── Request form: approval-code toggle ── */
    .req-toggle-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        margin-bottom: 14px;
    }

    .req-toggle-label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .req-toggle-switch {
        position: relative;
        width: 38px;
        height: 22px;
        flex-shrink: 0;
    }
    .req-toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .req-toggle-track {
        position: absolute;
        inset: 0;
        background: #d1d5db;
        border-radius: 999px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .req-toggle-track::before {
        content: '';
        position: absolute;
        height: 16px;
        width: 16px;
        left: 3px;
        top: 3px;
        background: #fff;
        border-radius: 50%;
        transition: transform 0.2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .req-toggle-switch input:checked + .req-toggle-track {
        background: #ef4444;
    }
    .req-toggle-switch input:checked + .req-toggle-track::before {
        transform: translateX(16px);
    }

    /* greyed-out normal fields while in code mode */
    #normalModePanel.req-disabled {
        opacity: 0.4;
        pointer-events: none;
        user-select: none;
    }

    .req-code-hint {
        font-size: 12px;
        color: #6b7280;
        background: #fef9c3;
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 8px 12px;
        margin-bottom: 10px;
        line-height: 1.5;
    }

    /* Dropzone */
    .attach-dropzone {
        border: 1.5px dashed #d1d5db;
        border-radius: 10px;
        background: #fafafa;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        cursor: pointer;
        margin: 5px 0 10px 0;
        transition: border-color .15s ease, background .15s ease;
    }

    .attach-dropzone:hover,
    .attach-dropzone.dragover {
        border-color: #dc2626;
        background: #fef2f2;
    }

    .attach-dropzone .icon-wrap {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #fee2e2;
        color: #dc2626;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .attach-dropzone .icon-wrap svg { width: 20px; height: 20px; }
    .attach-dropzone .copy { line-height: 1.35; }
    .attach-dropzone .copy .primary {
        font-size: 14px;
        font-weight: 600;
        color: #111827;
    }

    .attach-dropzone .copy .primary span {
        color: #dc2626;
        text-decoration: underline;
    }

    .attach-dropzone .copy .secondary {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 2px;
    }

    .attach-dropzone input[type="file"] { display: none; }
    
    /* File list */
    .attach-list {
        margin: 5px 0 4px 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .attach-row {
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px 12px;
        background: #fff;
    }

    .attach-row .thumb {
        width: 38px;
        height: 38px;
        border-radius: 6px;
        object-fit: cover;
        flex-shrink: 0;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
    }

    .attach-row .file-icon {
        width: 38px;
        height: 38px;
        border-radius: 6px;
        flex-shrink: 0;
        background: #eef2ff;
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .attach-row .file-icon.pdf { background: #fee2e2; color: #dc2626; }
    .attach-row .file-icon.doc { background: #dbeafe; color: #2563eb; }
    .attach-row .file-icon svg { width: 18px; height: 18px; }
    
    .attach-row .meta {
        flex: 1;
        min-width: 0;
    }

    .attach-row .meta .name {
        font-size: 13px;
        font-weight: 500;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .attach-row .meta .size {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 1px;
    }

    .attach-row .remove-btn {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: none;
        background: transparent;
        color: #9ca3af;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: background .15s ease, color .15s ease;
    }

    .attach-row .remove-btn:hover {
        background: #fee2e2;
        color: #dc2626;
    }

    .attach-row .remove-btn svg { width: 15px; height: 15px; }
    
    .attach-empty-hint {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 6px;
    }

    @media (max-width: 900px){
        .dashboard-main{
            grid-template-columns:1fr;
            flex:none;
        }

        .notifications{
            width:100%;
            height:auto;
        }

        .clock-card{
            width:100%;
            height:auto;
        }

        .announcement-grid{
            grid-template-columns:1fr;
        }

        .notif{
            padding:13px 36px 13px 16px;
        }

        .notif-time{
            font-size:10px;
        }

        .view-notification-meta {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    ['toast-success', 'toast-error'].forEach(function (toastId) {
        const toast = document.getElementById(toastId);
        if (!toast) {
            return;
        }

        setTimeout(function () {
            toast.classList.remove('opacity-0', 'scale-95');
        }, 100);

        setTimeout(function () {
            toast.classList.add('opacity-0', 'scale-95');
        }, 3000);

        setTimeout(function () {
            toast.remove();
        }, 3500);
    });
});
</script>

<div class="dashboard">

        <div class="header">
            <div class="p-2">
                <h1>Employee Dashboard</h1>
            </div>
            <div class="button-holder">
                <?php if (\App\Libraries\PortalAccess::reviewsRequests($user)): ?>
                <button onclick="openAnnouncementForm()" class="request-btn">
                    <img src="<?= base_url('icons/announcement.png') ?>" alt="Announcement" class="btn-icon">
                    <span>Announcement</span>
                </button>
                <?php endif; ?>
                <button onclick="openRequestForm()" class="request-btn">
                    <img src="<?= base_url('icons/Requests.png') ?>" alt="Request" class="btn-icon">
                    <span>Request Form</span>
                </button>
            </div>
        </div>
        
    <div class="dashboard-main">

        <div class="clock-card">

            <div class="clock-title">
                Welcome back, <?= esc($user->username) ?>!
            </div>

            <div class="clock-inner">

                <div class="status">
                    Status :
                        <span id="statusBadge">
                            <?= $currentAttendance ? 'Clocked In' : 'Clocked Out' ?>
                        </span>
                </div>

                <div class="clock">
                    <svg viewBox="0 0 200 200" width="100%" height="100%">
                        <circle cx="100" cy="100" r="90" stroke="#d1d5db" stroke-width="12" fill="none"/>
                        <circle id="progressRing" cx="100" cy="100" r="90" stroke="#84cc16" stroke-width="12" fill="none" stroke-linecap="round"/>
                        <circle id="progressDot" cx="100" cy="10" r="6" fill="white" stroke="#FFFEFE" stroke-width="4"/>
                    </svg>
                    <div id="shiftTime" class="shift-time">8:00</div>
                </div>

                <div id="clockDate" class="date"></div>
            </div>

            <?php
            $hasApprovedUT = false;
            $approvedUTHours = 0;
            $approvedUTReason = '';

            if ($currentAttendance) {
                $attendanceService = service('AttendanceComputationService');

                try {
                    $analysis =
                        $attendanceService->analyzeClockOut(
                            $user->id,
                            date('Y-m-d H:i:s'),
                            $currentAttendance
                        );
                } catch (\InvalidArgumentException $e) {
                    // Same legacy/malformed-schedule fallback as DashboardController::index() —
                    // this view must not crash a page the controller already recovered for.
                    $analysis = ['undertime' => ['approved_ut' => 0, 'unapproved_ut' => 0], 'overtime' => ['approved_ot' => 0, 'unapproved_ot' => 0]];
                }

                $hasApprovedUT =
                    $analysis['undertime']['approved_ut'] > 0;

                $approvedUTHours =
                    $analysis['undertime']['approved_ut'];

                if ($hasApprovedUT) {
                    $approvedRequest =
                        model(\App\Models\Client\RequestModel::class)
                            ->where('requested_by', $user->id)
                            ->where('request_type', 'undertime')
                            ->where('status', 'approved')
                            ->where('start_date <=', date('Y-m-d'))
                            ->where('end_date >=', date('Y-m-d'))
                            ->first();

                    $approvedUTReason =
                        $approvedRequest['reason']
                        ?? '';
                }
            }
            ?>

            <div class="buttons">
                <!-- functioning break button -->
                <?php if ($currentAttendance): ?>
                <form method="post" class="break-form" action="<?= site_url('client_portal/toggle-break/'.$currentAttendance['id']) ?>" onsubmit="this.querySelectorAll('button').forEach(b=>b.disabled=true)">
                    <input type="hidden" name="attendance_token" value="<?= esc($attendanceToken) ?>">
                    <input type="hidden" name="state_version" value="<?= (int) ($currentAttendance['state_version'] ?? 0) ?>">
                    <input type="hidden" name="break_action" value="<?= empty($currentAttendance['break_start']) ? 'start' : 'end' ?>">
                    <button type="submit" aria-label="<?= empty($currentAttendance['break_start']) ? 'Start break' : 'End break' ?>"
                    class="icon-btn coffee <?= $currentAttendance['break_start'] ? 'on-break' : '' ?>">
                        <span></span><span></span><span></span>
                    </button>
                </form>

                    <?php endif; ?>

                <button onclick="openTaskLog()" class="icon-btn idcard">
                <span></span><span></span>
                </button>

                <!--functioning toggle clock -->
                <?php if (!$currentAttendance): ?>
                <form
                    action="<?= site_url('client_portal/time-in') ?>"
                    method="post"
                    class="clock-form"
                    onsubmit="this.querySelectorAll('button').forEach(b=>b.disabled=true)">
                    <input type="hidden" name="attendance_token" value="<?= esc($attendanceToken) ?>">

                    <?= csrf_field() ?>

                    <button type="submit" class="clockout">
                        Clock In
                    </button>
                </form>
                <?php else: ?>
               <form
                    id="clockOutForm"
                    action="<?= site_url('client_portal/time-out/'.$currentAttendance['id']) ?>"
                    method="post"
                    class="clock-form">
                    <input type="hidden" name="attendance_token" value="<?= esc($attendanceToken) ?>">
                    <input type="hidden" name="state_version" value="<?= (int) ($currentAttendance['state_version'] ?? 0) ?>">

                    <?= csrf_field() ?>

                    <input
                        type="hidden"
                        name="unapproved_ut_reason"
                        id="unapproved_ut_reason">

                    <input
                        type="hidden"
                        name="missed_clockout_reason"
                        id="missed_clockout_reason">

                    <button
                        type="button"
                        class="clockout"
                        onclick="handleClockOut()">

                        Clock Out
                    </button>
                </form>
                <?php endif; ?>
            </div>

        </div>

        <?php
            $publishedItems = $publishedNotifications ?? [];
            $unpublishedItems = $unpublishedNotifications ?? [];

            $groupNotificationsByPeriod = static function (array $items): array {
                $today = new DateTimeImmutable('today');

                $buckets = [
                    'today' => [],
                    'older' => [],
                ];

                foreach ($items as $item) {
                    $stamp = strtotime((string) ($item['publish_at'] ?? ''));
                    $date = $stamp ? (new DateTimeImmutable())->setTimestamp($stamp) : $today;

                    if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
                        $buckets['today'][] = $item;
                        continue;
                    }

                    $buckets['older'][] = $item;
                }

                return [
                    'today' => [
                        'title' => 'Today',
                        'items' => $buckets['today'],
                    ],
                    'older' => [
                        'title' => 'Older',
                        'items' => $buckets['older'],
                    ],
                ];
            };

            $timeAgo = static function (string $dateTime): string {
                $stamp = strtotime($dateTime);
                if (! $stamp) {
                    return 'just now';
                }

                $diff = time() - $stamp;
                if ($diff < 60) {
                    return $diff . 'sec ago';
                }
                if ($diff < 3600) {
                    return floor($diff / 60) . 'min ago';
                }
                if ($diff < 86400) {
                    return floor($diff / 3600) . 'hr ago';
                }
                if ($diff < 604800) {
                    return floor($diff / 86400) . 'd ago';
                }

                return floor($diff / 604800) . 'w ago';
            };

            $initials = static function (string $title): string {
                $clean = trim($title);
                if ($clean === '') {
                    return 'AN';
                }

                $parts = preg_split('/\s+/', $clean) ?: [];
                $letters = '';

                foreach ($parts as $part) {
                    if ($part !== '') {
                        $letters .= strtoupper(substr($part, 0, 1));
                    }
                    if (strlen($letters) >= 2) {
                        break;
                    }
                }

                return str_pad(substr($letters, 0, 2), 2, 'N');
            };

            $renderNotificationItem = static function (array $n, callable $initials, callable $timeAgo, bool $showMeta = false): void {
                $notification = notification_data($n);
                ?>
                <div
                    class="notif <?= ! empty($n['is_viewed']) ? 'notif-viewed' : '' ?>"
                    role="button"
                    tabindex="0"
                    data-id="<?= esc((string) $notification['id'], 'attr') ?>"
                    data-title="<?= esc($notification['title'], 'attr') ?>"
                    
                    data-details="<?= esc($notification['details'], 'attr') ?>"
                    data-publish="<?= esc($notification['published_at'] !== '' ? date('M d, Y h:i A', strtotime($notification['published_at'])) : 'N/A', 'attr') ?>"
                    data-category="<?= esc($notification['category'], 'attr') ?>"
                    data-priority="<?= esc(ucfirst($notification['priority']), 'attr') ?>"
                    data-audience="<?= esc(ucfirst($notification['audience']), 'attr') ?>"
                    data-status="<?= esc($notification['status'], 'attr') ?>"
                    data-source="<?= esc($notification['source'], 'attr') ?>"
                    data-request-id="<?= esc((string) $notification['request_id'], 'attr') ?>"
                    data-actor-name="<?= esc($notification['actor_name'], 'attr') ?>"
                    data-actor-position="<?= esc($notification['actor_position'], 'attr') ?>"
                    data-author-name="<?= esc($notification['author_name'], 'attr') ?>"
                    data-author-photo="<?= esc($notification['author_photo'], 'attr') ?>"
                    data-requester-name="<?= esc($notification['requester_name'], 'attr') ?>"
                    data-requester-department="<?= esc($notification['requester_department'], 'attr') ?>"
                    data-requester-photo="<?= esc($notification['requester_photo'], 'attr') ?>"
                    data-request-type="<?= esc($notification['request_type'], 'attr') ?>"
                    data-attachments="<?= esc(json_encode($notification['attachments'] ?? []), 'attr') ?>"
                    onclick="openNotificationModal(this)"
                    onkeydown="handleNotificationKey(event, this)">
                    <div class="notif-avatar"><?= esc((string) $initials((string) ($n['requester_name'] ?? $notification['title'] ?? ''))) ?></div>
                    <div class="notif-content">
                        <div class="notif-title-row">
                            <div class="notif-title"><?= esc($notification['title']) ?></div>
                            <div class="notif-time"><?= esc((string) $timeAgo((string) ($notification['published_at'] ?? ''))) ?></div>
                        </div>
                        <div class="notif-desc"><?= esc(mb_strimwidth($notification['details'], 0, 80, '...')) ?></div>
                        <div class="notif-tags">
                            <span class="notif-pill priority-<?= strtolower($notification['priority']) ?>"><?= esc(ucfirst($notification['priority'])) ?></span>
                            <span class="notif-pill muted"><?= esc(ucfirst($notification['category'])) ?></span>
                        </div>
                        <?php if ($showMeta): ?>
                        <div class="notif-meta">Publish on <?= esc(date('M d, Y h:i A', strtotime((string) ($notification['published_at'] ?? 'now')))) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            };

            $publishedBuckets = $groupNotificationsByPeriod($publishedItems);
            $unpublishedBuckets = $groupNotificationsByPeriod($unpublishedItems);

            $hasPublishedTodayItems = ! empty($publishedBuckets['today']['items']);
            $hasPublishedOlderItems = ! empty($publishedBuckets['older']['items']);
            $hasPublishedItems = $hasPublishedTodayItems || $hasPublishedOlderItems;

            $hasUnpublishedTodayItems = ! empty($unpublishedBuckets['today']['items']);
            $hasUnpublishedOlderItems = ! empty($unpublishedBuckets['older']['items']);
            $hasUnpublishedItems = $hasUnpublishedTodayItems || $hasUnpublishedOlderItems;

            $renderNotificationSection = static function (string $sectionClass, string $title, array $items, callable $renderItem, callable $initials, callable $timeAgo, bool $showMeta = false): void {
                if (empty($items)) {
                    return;
                }
                ?>
                <div class="notifications-section <?= esc($sectionClass, 'attr') ?>">
                    <div class="notifications-divider-title"><?= esc($title) ?></div>
                    <?php foreach ($items as $n): ?>
                        <?php $renderItem($n, $initials, $timeAgo, $showMeta); ?>
                    <?php endforeach; ?>
                </div>
                <?php
            };
        ?>

        <div class="notifications" id="notificationsPanel">
            <div class="notifications-top">
                <div class="notifications-title">Notification</div>
                <button type="button" class="notifications-clear" onclick="clearNotificationsView()"><img class="dot" src="<?= base_url('icons/Clear.png') ?>">Clear all</button>
            </div>

            <div class="notifications-divider"></div>

            <div class="notifications-tabs">
                <button type="button" id="tabPublished" class="notifications-tab active" onclick="toggleNotificationsGroup('published')">
                    Published
                    <span class="notifications-badge"><?= count($publishedItems) ?></span>
                </button>

                <?php if (\App\Libraries\PortalAccess::can('announcements', $user)): ?>
                <button type="button" id="tabUnpublished" class="notifications-tab" onclick="toggleNotificationsGroup('unpublished')">
                    Unpublished
                    <span class="notifications-badge"><?= count($unpublishedItems) ?></span>
                </button>
                <?php endif; ?>
            </div>

            <div id="publishedGroup" class="notifications-group hide-scroll-bar active">
                <?php if ($hasPublishedItems): ?>
                    <?php
                        if ($hasPublishedTodayItems) {
                            $renderNotificationSection('today', $publishedBuckets['today']['title'], $publishedBuckets['today']['items'], $renderNotificationItem, $initials, $timeAgo);
                        }

                        if ($hasPublishedOlderItems) {
                            $renderNotificationSection('older', $publishedBuckets['older']['title'], $publishedBuckets['older']['items'], $renderNotificationItem, $initials, $timeAgo);
                        }
                    ?>
                <?php else: ?>
                    <div class="notifications-empty">No notifications</div>
                <?php endif; ?>
            </div>

            <?php if (\App\Libraries\PortalAccess::can('announcements', $user)): ?>
            <div id="unpublishedGroup" class="notifications-group hide-scroll-bar">
                <?php if ($hasUnpublishedItems): ?>
                    <?php
                        if ($hasUnpublishedTodayItems) {
                            $renderNotificationSection('today', $unpublishedBuckets['today']['title'], $unpublishedBuckets['today']['items'], $renderNotificationItem, $initials, $timeAgo, true);
                        }

                        if ($hasUnpublishedOlderItems) {
                            $renderNotificationSection('older', $unpublishedBuckets['older']['title'], $unpublishedBuckets['older']['items'], $renderNotificationItem, $initials, $timeAgo, true);
                        }
                    ?>
                <?php else: ?>
                    <div class="notifications-empty">No notifications</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

    <!-- TASK LOG MODAL -->
    <div class="modal-overlay" id="taskLogModal">
        <div class="modal">
            <div class="modal-header">
                <div>
                    <span>Task Log</span>
                    <p id="task_att_date" class="text-xs text-gray-400 font-normal mt-0.5"></p>
                </div>
                <button onclick="closeTaskLogModal()">✕</button>
            </div>

            <div class="modal-body">

                <!-- TIMELINE -->
                <div class="mb-4">
                    <div class="flex items-center justify-between" style="margin-bottom:6px;">
                        <label class="text-xs font-semibold text-gray-500">Your Clocked-In Time</label>
                        <span id="tl_window_label" class="text-xs text-gray-400"></span>
                    </div>
                    <div id="tl_timeline" class="relative w-full bg-gray-100 rounded-lg overflow-hidden border border-gray-200" style="height:32px;cursor:pointer;"></div>
                    <p class="text-[11px] text-gray-400 mt-1">Colored blocks are already-logged tasks — click a free (empty) spot to start a new one there.</p>
                </div>

                <!-- TASK LIST -->
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-500 block" style="margin-bottom:6px;">Logged Tasks</label>
                    <div id="tl_task_list"></div>
                    <p id="tl_empty_msg" class="text-xs text-gray-400 text-center py-3 hidden">No tasks logged yet for this session.</p>
                </div>

                <!-- ADD/EDIT FORM -->
                <div class="border-t border-gray-100 pt-4">
                    <div class="flex items-center justify-between mb-1">
                        <label id="tl_form_title" class="text-xs font-semibold text-gray-700">Add a Task</label>
                        <button type="button" id="tl_cancel_edit_btn" onclick="cancelTaskEdit()" class="hidden text-xs text-purple-600 hover:text-purple-700 font-semibold">Cancel edit</button>
                    </div>

                    <label>Select Preset Task</label>
                    <select id="savedTasksDropdown">
                        <option value="">-- Write your task manually --</option>
                    </select>

                    <label>Or write your task *</label>
                    <textarea id="task_att_text" placeholder="e.g. Coding Session"></textarea>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label>Start Time *</label>
                            <input type="hidden" id="task_start_time">
                            <div class="tl-time-row">
                                <select id="task_start_hour" class="tl-time-select"></select>
                                <span class="tl-time-colon">:</span>
                                <select id="task_start_minute" class="tl-time-select"></select>
                                <select id="task_start_ampm" class="tl-time-select tl-time-ampm"></select>
                            </div>
                        </div>
                        <div>
                            <label>End Time *</label>
                            <input type="hidden" id="task_end_time">
                            <div class="tl-time-row">
                                <select id="task_end_hour" class="tl-time-select"></select>
                                <span class="tl-time-colon">:</span>
                                <select id="task_end_minute" class="tl-time-select"></select>
                                <select id="task_end_ampm" class="tl-time-select tl-time-ampm"></select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between" style="margin-top:-6px;margin-bottom:10px;">
                        <div class="flex" style="gap:6px;">
                            <button type="button" onclick="applyDurationPreset(15)" class="tl-duration-chip">15m</button>
                            <button type="button" onclick="applyDurationPreset(30)" class="tl-duration-chip">30m</button>
                            <button type="button" onclick="applyDurationPreset(60)" class="tl-duration-chip">1h</button>
                            <button type="button" onclick="applyDurationPreset(120)" class="tl-duration-chip">2h</button>
                        </div>
                        <span id="tl_duration_label" style="font-size:11px;font-weight:600;color:#6b7280;"></span>
                    </div>

                    <label>Client</label>
                    <input type="text" id="task_att_client" placeholder="e.g. Acme Corp">

                    <label class="checkbox-container">
                        <input type="checkbox" id="saveTaskToggle">
                        Save this task as a preset
                    </label>

                    <p id="tl_form_error" class="text-xs text-red-600 mb-2 hidden" style="margin-top:-4px;"></p>

                    <input type="hidden" id="task_att_id">
                    <input type="hidden" id="task_editing_id" value="">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="cancel-btn" onclick="closeTaskLogModal()">Close</button>
                <button type="button" id="tl_save_btn" class="submit-btn" onclick="saveTask()">Add Task</button>
            </div>
        </div>
    </div>

    <!-- REQUEST MODAL -->
    <div class="modal-overlay" id="requestModal">
        <div class="modal">
            <form id="requestForm" action="<?= site_url('client_portal/requests/store') ?>" method="post">
                <!-- tells the controller which mode we're in -->
                <input type="hidden" name="code_mode" id="codeModeInput" value="0">

                <div class="modal-header">
                    <span>Request Form</span>
                    <button type="button" onclick="closeRequestForm()">✕</button>
                </div>

                <div class="modal-body">

                    <!-- ── Code-mode toggle ── -->
                    <div class="req-toggle-row">
                        <label class="req-toggle-switch" for="codeModeToggle">
                            <input type="checkbox" id="codeModeToggle" onchange="toggleRequestCodeMode(this.checked)">
                            <span class="req-toggle-track"></span>
                        </label>
                        <span class="req-toggle-label">I have an approval code</span>
                    </div>

                    <!-- ── Code-mode panel (shown when toggle is ON) ── -->
                    <div id="codeModePanel" style="display:none;">
                        <div class="req-code-hint" id="codeHintText">
                            Enter your approval code to continue.
                        </div>
                        <label>Approval Code</label>
                        <input type="text" id="codeModeCodeInput" name="approval_code"
                               placeholder="e.g. A3KM-PQ72-XYRS-84NV"
                               style="text-transform:uppercase;letter-spacing:2px;font-family:monospace;font-size:15px;"
                               oninput="this.value=this.value.toUpperCase()"
                               onblur="applyApprovalCode(this.value)">
                    </div>

                    <!-- ── Normal-mode fields (always visible, locked only when preset data exists) ── -->
                    <div id="normalModePanel">
                        <label>Select Form</label>
                        <select name="request_type" id="reqType" required onchange="toggleLeaveCategoryField(this.value)">
                            <option value="leave">Leave Request</option>
                            <option value="overtime">Overtime Request</option>
                            <option value="undertime">Undertime Request</option>
                        </select>

                        <div id="leaveCategoryWrap">
                            <label>Leave Type</label>
                            <select name="leave_category" id="reqLeaveCategory">
                                <option value="vacation">Vacation Leave</option>
                                <option value="sick">Sick Leave</option>
                                <option value="emergency">Emergency Leave</option>
                            </select>
                        </div>

                        <label>Start Date</label>
                        <input type="date" name="start_date" id="reqStartDate" required oninput="checkBackdatedRequest()">

                        <div id="reqBackdatedNotice" style="display:none;background:#EDE9FE;color:#5B21B6;font-size:12px;font-weight:600;padding:6px 10px;border-radius:6px;margin:-4px 0 10px;">
                            ⓘ This date has already passed — this will be filed as a backdated request for payroll to review.
                        </div>

                        <label>End Date</label>
                        <input type="date" name="end_date" id="reqEndDate">

                        <label>Message</label>
                        <textarea rows="3" name="reason" id="reqReason" placeholder="Enter your reason..." required></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="cancel-btn" onclick="closeRequestForm()">Cancel</button>
                    <button type="submit" class="submit-btn">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ANNOUNCEMENT MODAL -->
    <div class="modal-overlay" id="announcementModal">
        <div class="modal announcement-modal">
            <form id="announcementForm" action="<?= site_url('client_portal/announcements/store') ?>" method="post" enctype="multipart/form-data" class="announcement-form" data-store-url="<?= site_url('client_portal/announcements/store') ?>">
                <div class="modal-header">
                    <span id="announcementModalTitle">Create Announcement</span>
                    <button type="button" onclick="closeAnnouncementForm()">✕</button>
                </div>

                <div class="modal-body hide-scroll-bar">
                    <div class="announcement-grid">
                        <div class="announcement-field full-span">
                            <label>Announcement Title *</label>
                            <input type="text" name="title" maxlength="120" placeholder="e.g. Payroll Release Schedule - April 2026" required>
                        </div>

                        <div class="announcement-field">
                            <label>Category *</label>
                            <select name="category" required>
                                <option value="">Select category</option>
                                <option value="general">General Update</option>
                                <option value="policy">Policy Notice</option>
                                <option value="event">Event</option>
                                <option value="maintenance">System Maintenance</option>
                                <option value="emergency">Urgent Alert</option>
                            </select>
                        </div>

                        <div class="announcement-field">
                            <label>Audience *</label>
                            <select id="announcementAudience" name="audience" onchange="toggleAnnouncementAudience()" required>
                                <option value="">Select audience</option>
                                <option value="all">All Employees</option>
                                <option value="payroll">Payroll Staff</option>
                                <option value="department">Specific Department</option>
                            </select>
                        </div>

                        <div id="announcementDepartmentWrap" class="announcement-field" style="display:none;">
                            <label>Department *</label>
                            <input id="announcementDepartment" type="text" name="department" placeholder="e.g. Finance">
                        </div>

                        <div class="announcement-field">
                            <label>Priority *</label>
                            <select name="priority" required>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="announcement-field">
                            <label>Publish On *</label>
                            <input type="datetime-local" name="publish_at" required>
                        </div>

                        <div class="announcement-field">
                            <label>Visible Until</label>
                            <input type="datetime-local" name="expires_at">
                        </div>

                        <div class="announcement-field full-span" id="announcementExistingAttachmentsWrap" style="display:none;">
                            <label>Current Attachments</label>
                            <div class="attach-list" id="announcementExistingAttachments"></div>
                        </div>

                        <div class="announcement-field full-span">
                            <label>Attachments</label>

                            <div class="attach-dropzone" id="dropzone">
                                <div class="icon-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.44 11.05l-8.49 8.49a5 5 0 01-7.07 0 5 5 0 010-7.07l8.49-8.49a3.5 3.5 0 014.95 4.95L11.83 18.97a2 2 0 01-2.83 0 2 2 0 010-2.83l6.36-6.36"/>
                                </svg>
                                </div>
                                <div class="copy">
                                <div class="primary"><span>Click to upload</span> or drag and drop</div>
                                <div class="secondary">PDF, DOC, or image — up to 10MB each</div>
                                </div>
                                <input type="file" id="fileInput" name="attachments[]" multiple accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                            </div>
                            
                            <div class="attach-list" id="attachList"></div>
                        </div>

                        <div class="announcement-field full-span">
                            <label>Announcement Details *</label>
                            <textarea name="details" style="min-height:120px;" placeholder="Write complete announcement details here." required></textarea>
                        </div>

                        <div class="announcement-field full-span">
                            <label class="announcement-check">
                                <input type="checkbox" name="require_acknowledgement" value="1">
                                Require employee acknowledgement
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="submit-btn" id="announcementSubmitBtn">Publish</button>
                </div>
            </form>
        </div>
    </div>

        <div class="modal-overlay" id="undertimeModal">
            <div class="modal">

                <div class="modal-header">
                    <span>Early Clock Out</span>
                    <button
                        type="button"
                        onclick="closeUndertimeModal()"
                    >
                        ✕
                    </button>
                </div>

                <div class="modal-body">

                    <p style="
                        color:#dc2626;
                        font-weight:600;
                        margin-bottom:15px;
                    ">
                        You are clocking out before your
                        scheduled end time.
                    </p>

                    <p style="
                        margin-bottom:20px;
                        color:#6b7280;
                    ">
                        If you do not have an approved
                        undertime request, this may result
                        in salary deduction.
                    </p>

                   <?php if ($hasApprovedUT): ?>

                    <div
                        class="bg-green-50 border border-green-200 rounded-lg p-3 mt-3">

                        <p class="text-green-700 font-semibold">
                            You have an APPROVED undertime request.
                        </p>

                        <p class="text-sm text-gray-600 mt-2">
                            Approved Hours:
                            <strong>
                                <?= $approvedUTHours ?>
                            </strong>
                        </p>

                        <?php if ($approvedUTReason): ?>
                            <p class="text-sm text-gray-600">
                                Reason:
                                <strong>
                                    <?= esc($approvedUTReason) ?>
                                </strong>
                            </p>
                        <?php endif; ?>

                        <p class="text-sm text-green-600 mt-2">
                            You may proceed to clock out.
                        </p>

                    </div>

                    <?php else: ?>

                    <label>
                        Reason for Early Clock Out
                    </label>

                    <textarea
                        id="earlyClockOutReason"
                        placeholder="Enter your reason..."
                    ></textarea>

                    <?php endif; ?>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="cancel-btn"
                        onclick="closeUndertimeModal()"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="submit-btn"
                        onclick="proceedClockOut()"
                    >
                        Proceed Clock Out
                    </button>

                </div>

            </div>
        </div>

        <div class="modal-overlay" id="missedClockoutModal">
            <div class="modal">

                <div class="modal-header">
                    <span>Missed Clock-Out</span>
                    <button
                        type="button"
                        onclick="closeMissedClockoutModal()"
                    >
                        ✕
                    </button>
                </div>

                <div class="modal-body">

                    <p style="
                        color:#dc2626;
                        font-weight:600;
                        margin-bottom:15px;
                    ">
                        It looks like you forgot to clock out
                        on your last shift.
                    </p>

                    <p style="
                        margin-bottom:20px;
                        color:#6b7280;
                    ">
                        Please provide a reason so payroll can
                        review this attendance record.
                    </p>

                    <label>
                        Reason for Late Clock-Out
                    </label>

                    <textarea
                        id="missedClockoutReason"
                        placeholder="Enter your reason..."
                    ></textarea>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="cancel-btn"
                        onclick="closeMissedClockoutModal()"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="submit-btn"
                        onclick="proceedMissedClockout()"
                    >
                        Proceed Clock Out
                    </button>

                </div>

            </div>
        </div>

<script>
    const attendance = <?= json_encode($currentAttendance ?? null) ?>;
    const scheduledEnd = <?= json_encode($currentAttendance['scheduled_end'] ?? null) ?>;
    console.log('scheduledEnd:', scheduledEnd);
    console.log('currentAttendance:', attendance);

    // Required globals for public/js/components/task-log.js (loaded below).
    const TASKS_BASE_URL  = "<?= site_url('client_portal/timesheet/tasks') ?>";
    const UPDATE_TASK_URL = "<?= site_url('client_portal/timesheet/tasks/edit') ?>";
    const DELETE_TASK_URL = "<?= site_url('client_portal/timesheet/tasks/delete') ?>";
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>]/g, m => m === '&' ? '&amp;' : (m === '<' ? '&lt;' : '&gt;'));
    }

    async function loadSavedTasks() {
        try {
            const response = await fetch("<?= site_url('client_portal/tasks/saved') ?>");
            const tasks = await response.json();

            const dropdown = document.getElementById("savedTasksDropdown");
            dropdown.innerHTML = '<option value="">-- Write your task manually --</option>';

            tasks.forEach(task => {
                const option = document.createElement("option");
                option.value = task.task_performed;
                option.text = task.task_performed;
                dropdown.appendChild(option);
            });

            // attach change listener here after options are loaded
            dropdown.onchange = function() {
                document.getElementById("task_att_text").value = this.value;
            };

        } catch (err) {
            console.error("Failed to load saved tasks:", err);
        }
    }

    /* MODALS */
    function openTaskLog() {
        if (!attendance) {
            if (typeof showToast === 'function') showToast('You must be clocked in to log a task.', 'warning');
            return;
        }
        loadSavedTasks();
        const today = new Date().toISOString().slice(0, 10);
        openTaskLogModal(attendance.id, today);
    }

    function openRequestForm(){
        document.getElementById("requestModal").style.display="flex";
    }

    // Opens the Request form pre-filled as an Overtime request for a specific
    // date — used right after a missed clock-out, so the employee doesn't have
    // to already know this step exists in order to get those hours paid.
    function openLateOvertimeRequest(dateStr, reason) {
        openRequestForm();

        const typeSelect = document.getElementById('reqType');
        typeSelect.value = 'overtime';
        toggleLeaveCategoryField('overtime');

        document.getElementById('reqStartDate').value = dateStr;
        document.getElementById('reqEndDate').value = dateStr;
        document.getElementById('reqReason').value = reason || '';

        checkBackdatedRequest();

        if (typeof showToast === 'function') {
            showToast('These extra hours are only paid once an Overtime request for this date is approved — review and submit below.', 'warning');
        }
    }

    function closeRequestForm(){
        document.getElementById("requestModal").style.display="none";
        // Reset toggle back to normal mode when closing
        const toggle = document.getElementById("codeModeToggle");
        if (toggle && toggle.checked) {
            toggle.checked = false;
            toggleRequestCodeMode(false);
        }
        resetPresetState();
        document.getElementById("requestForm").reset();
        document.getElementById("reqBackdatedNotice").style.display = "none";
    }

    function checkBackdatedRequest() {
        const input  = document.getElementById("reqStartDate");
        const notice = document.getElementById("reqBackdatedNotice");
        if (!input.value) {
            notice.style.display = "none";
            return;
        }

        const [y, m, d] = input.value.split('-').map(Number);
        const picked    = new Date(y, m - 1, d);

        const now   = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

        notice.style.display = (picked < today) ? "block" : "none";
    }

    function toggleRequestCodeMode(on) {
        const codePanel   = document.getElementById("codeModePanel");
        const hiddenInput = document.getElementById("codeModeInput");

        if (on) {
            codePanel.style.display = "block";
            hiddenInput.value       = "1";
            // Normal fields stay visible + required; they only get locked if
            // a looked-up code carries preset data (see applyApprovalCode).
            setNormalFieldsLocked(false);
            document.getElementById("codeModeCodeInput").focus();
        } else {
            codePanel.style.display = "none";
            hiddenInput.value       = "0";
            // Clear code input and unlock all normal fields
            document.getElementById("codeModeCodeInput").value = "";
            resetPresetState();
        }
    }

    function toggleLeaveCategoryField(type) {
        const wrap = document.getElementById('leaveCategoryWrap');
        if (wrap) wrap.style.display = (type === 'leave') ? '' : 'none';
    }

    // Tracks active preset duration (days) when a code provides one
    let _currentPresetDuration = null;

    // Helper: format Date -> YYYY-MM-DD
    function _formatYMD(d) {
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    // Set end-date `max` based on start date + (duration - 1)
    function setEndDateMaxForPreset(duration) {
        const startEl = document.getElementById('reqStartDate');
        const endEl   = document.getElementById('reqEndDate');
        if (!endEl) return;

        _currentPresetDuration = duration !== null ? Number(duration) : null;

        if (_currentPresetDuration === null) {
            endEl.removeAttribute('max');
            return;
        }

        // If start date exists, compute max; otherwise do not set max yet
        const startVal = startEl ? startEl.value : '';
        if (!startVal) {
            endEl.removeAttribute('max');
            return;
        }

        const startDate = new Date(startVal + 'T00:00:00');
        if (isNaN(startDate.getTime())) {
            endEl.removeAttribute('max');
            return;
        }

        const maxDate = new Date(startDate);
        maxDate.setDate(maxDate.getDate() + (_currentPresetDuration - 1));
        const maxYmd = _formatYMD(maxDate);
        endEl.setAttribute('max', maxYmd);

        // If end empty, default it to the computed end date
        if (!endEl.value) {
            endEl.value = maxYmd;
        }

        // Enforce current value not past max
        if (endEl.value > maxYmd) {
            endEl.value = maxYmd;
            const hint = document.getElementById('codeHintText');
            if (hint) {
                hint.style.background  = '#fef3c7';
                hint.style.borderColor = '#facc15';
                hint.innerHTML = `⚠️ End date limited to ${_currentPresetDuration} day${_currentPresetDuration === 1 ? '' : 's'} by preset.`;
            }
        }
    }

    // Enforce end date does not exceed max when user edits it
    function enforceEndDateLimitOnChange() {
        const endEl = document.getElementById('reqEndDate');
        if (!endEl) return;
        const max = endEl.getAttribute('max');
        if (!max) return;
        if (endEl.value && endEl.value > max) {
            endEl.value = max;
            const hint = document.getElementById('codeHintText');
            if (hint) {
                hint.style.background  = '#fef3c7';
                hint.style.borderColor = '#facc15';
                hint.innerHTML = `⚠️ End date cannot exceed the preset duration (${_currentPresetDuration} day${_currentPresetDuration === 1 ? '' : 's'}).`;
            }
        }
    }

    // Attach listeners so start/end changes update enforcement
    (function attachPresetDateListeners() {
        const startEl = document.getElementById('reqStartDate');
        const endEl   = document.getElementById('reqEndDate');
        if (startEl) {
            startEl.addEventListener('change', () => {
                if (_currentPresetDuration !== null) setEndDateMaxForPreset(_currentPresetDuration);
            });
        }
        if (endEl) {
            endEl.addEventListener('change', enforceEndDateLimitOnChange);
            endEl.addEventListener('input', enforceEndDateLimitOnChange);
        }
    })();

    // Lock/unlock individual preset-capable fields
    function setNormalFieldsLocked(locked) {
        const fields = ["reqType", "reqLeaveCategory", "reqStartDate", "reqEndDate", "reqReason"];
        fields.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.disabled = locked;
            el.style.opacity = locked ? "0.5" : "";
        });
    }

    function resetPresetState() {
        _currentPresetDuration = null;
        setNormalFieldsLocked(false);
        // Remove any end-date max applied by a preset
        const endEl = document.getElementById('reqEndDate');
        if (endEl) endEl.removeAttribute('max');
        // Restore field values to empty
        document.getElementById("reqType").value      = "leave";
        document.getElementById("reqStartDate").value = "";
        document.getElementById("reqEndDate").value   = "";
        document.getElementById("reqReason").value    = "";
        // Restore required on text fields
        document.getElementById("reqType").required      = true;
        document.getElementById("reqStartDate").required = true;
        document.getElementById("reqReason").required    = true;
        // Reset hint
        const hint = document.getElementById("codeHintText");
        if (hint) {
            hint.style.background = "";
            hint.style.borderColor = "";
            hint.innerHTML = "Enter your approval code to continue.";
        }
    }

    async function applyApprovalCode(rawCode) {
        const code = rawCode.trim().toUpperCase();
        if (!code) return;

        const hint = document.getElementById("codeHintText");

        try {
            const res  = await fetch("<?= site_url('client_portal/requests/lookup-code') ?>?code=" + encodeURIComponent(code));
            const data = await res.json();

            if (!data.success) {
                // Invalid/used code — show warning but don't block the fields
                hint.style.background  = "#fee2e2";
                hint.style.borderColor = "#fca5a5";
                hint.innerHTML = "⚠️ " + (data.message || "Invalid or already-used code.");
                setNormalFieldsLocked(false);
                return;
            }

            const preset = data.preset_data || {};
            const presetDuration = preset.duration && Number(preset.duration) > 0 ? Number(preset.duration) : null;

            setNormalFieldsLocked(false);
            hint.style.background  = "#dcfce7";
            hint.style.borderColor = "#86efac";
            hint.innerHTML = presetDuration !== null
                ? `✅ Code accepted. Duration preset: ${presetDuration} day${presetDuration === 1 ? "" : "s"}.`
                : "✅ Code accepted. You can fill in the request details below.";

            // Activate client-side enforcement for end date when preset duration exists
            if (presetDuration !== null) {
                setEndDateMaxForPreset(presetDuration);
            } else {
                setEndDateMaxForPreset(null);
            }

        } catch (e) {
            hint.style.background  = "#fee2e2";
            hint.style.borderColor = "#fca5a5";
            hint.innerHTML = "⚠️ Could not verify the code. Please check your connection and try again.";
        }
    }

    function openAnnouncementForm() {
        resetAnnouncementFormToCreateMode();
        document.getElementById("announcementModal").style.display="flex";
    }

    function closeAnnouncementForm() {
        document.getElementById("announcementModal").style.display="none";
        resetAnnouncementFormToCreateMode();
    }

    function resetAnnouncementFormToCreateMode() {
        const form = document.getElementById("announcementForm");
        form.reset();
        form.action = form.dataset.storeUrl;
        delete form.dataset.editingId;
        document.getElementById("announcementModalTitle").textContent = "Create Announcement";
        document.getElementById("announcementSubmitBtn").textContent = "Publish";

        const existingWrap = document.getElementById("announcementExistingAttachmentsWrap");
        const existingList = document.getElementById("announcementExistingAttachments");
        if (existingWrap) existingWrap.style.display = "none";
        if (existingList) existingList.innerHTML = "";

        if (typeof files !== "undefined") {
            files.length = 0;
            if (typeof syncFileInput === "function") syncFileInput();
            if (typeof render === "function") render();
        }

        toggleAnnouncementAudience();
    }

    function renderExistingAnnouncementAttachments(announcementId, attachments) {
        const wrap = document.getElementById("announcementExistingAttachmentsWrap");
        const list = document.getElementById("announcementExistingAttachments");
        if (!list) return;

        list.innerHTML = '';
        attachments.forEach(function (att) {
            const row = document.createElement('div');
            row.className = 'attach-row';
            row.innerHTML = `
                <div class="file-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg></div>
                <div class="meta">
                    <div class="name">${att.name}</div>
                </div>
            `;

            const actions = document.createElement('div');
            actions.style.display = 'flex';
            actions.style.gap = '6px';

            const viewLink = document.createElement('a');
            viewLink.href = att.url;
            viewLink.target = '_blank';
            viewLink.rel = 'noopener';
            viewLink.className = 'remove-btn';
            viewLink.setAttribute('aria-label', 'View ' + att.name);
            viewLink.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-btn';
            removeBtn.setAttribute('aria-label', 'Remove ' + att.name);
            removeBtn.title = 'Remove this attachment';
            removeBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>';
            removeBtn.addEventListener('click', function () {
                deleteExistingAnnouncementAttachment(announcementId, att.id, row);
            });

            actions.appendChild(viewLink);
            actions.appendChild(removeBtn);
            row.appendChild(actions);
            list.appendChild(row);
        });

        if (wrap) wrap.style.display = attachments.length > 0 ? '' : 'none';
    }

    async function deleteExistingAnnouncementAttachment(announcementId, attachmentId, rowEl) {
        if (!attachmentId || !window.announcementAttachmentDeleteUrlBase) return;
        if (!window.confirm('Remove this attachment? This cannot be undone.')) return;

        try {
            const response = await fetch(`${window.announcementAttachmentDeleteUrlBase}/${attachmentId}`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const result = await response.json();

            if (!response.ok || !result || result.success !== true) {
                showToast((result && result.message) || 'Failed to remove attachment.', 'error');
                return;
            }

            showToast(result.message || 'Attachment removed.', 'success');
            if (rowEl) rowEl.remove();

            const list = document.getElementById("announcementExistingAttachments");
            const wrap = document.getElementById("announcementExistingAttachmentsWrap");
            if (wrap && list && list.children.length === 0) wrap.style.display = 'none';

            // Keep the bell dropdown's cached attachment data (and any
            // already-open view modal) from still showing the file that was
            // just removed.
            document.querySelectorAll(
                `.header-notif-item[data-id="${announcementId}"][data-source="announcement"], .notif[data-id="${announcementId}"][data-source="announcement"]`
            ).forEach(function (item) {
                let current = [];
                try { current = JSON.parse(item.dataset.attachments || '[]'); } catch (e) { current = []; }
                const remaining = current.filter(function (a) {
                    return !String(a.file_path || '').endsWith('/announcements/download/' + attachmentId);
                });
                item.dataset.attachments = JSON.stringify(remaining);
            });
        } catch (error) {
            console.error('Unable to remove attachment.', error);
            showToast('Unable to remove attachment. Please check your connection and try again.', 'error');
        }
    }

    async function openAnnouncementEditModal(announcementId) {
        if (!announcementId || !window.announcementEditFetchUrlBase) return;

        let data;
        try {
            const response = await fetch(`${window.announcementEditFetchUrlBase}/${announcementId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            data = await response.json();
            if (!response.ok || !data || data.success !== true) {
                showToast((data && data.message) || 'Unable to load announcement for editing.', 'error');
                return;
            }
        } catch (error) {
            console.error('Unable to load announcement for editing.', error);
            showToast('Unable to load announcement for editing.', 'error');
            return;
        }

        const a = data.announcement;
        const form = document.getElementById("announcementForm");

        resetAnnouncementFormToCreateMode();

        form.action = `${window.announcementUpdateUrlBase}/${a.id}`;
        form.dataset.editingId = String(a.id);
        form.elements['title'].value = a.title || '';
        form.elements['category'].value = a.category || '';
        form.elements['audience'].value = a.audience || '';
        form.elements['department'].value = a.department || '';
        form.elements['priority'].value = a.priority || 'normal';
        form.elements['publish_at'].value = a.publish_at || '';
        form.elements['expires_at'].value = a.expires_at || '';
        form.elements['details'].value = a.details || '';
        form.elements['require_acknowledgement'].checked = !!a.require_acknowledgement;
        toggleAnnouncementAudience();

        document.getElementById("announcementModalTitle").textContent = "Edit Announcement";
        document.getElementById("announcementSubmitBtn").textContent = "Save Changes";

        renderExistingAnnouncementAttachments(a.id, data.attachments || []);

        document.getElementById("announcementModal").style.display = "flex";
    }
    window.openAnnouncementEditModal = openAnnouncementEditModal;

    // The bell dropdown's notification rows carry their data (title,
    // details, attachments, ...) in HTML data-* attributes rendered once at
    // page load. Editing saves via fetch with no page reload, so without
    // this the dropdown (and any already-open view modal) would keep
    // showing the pre-edit data — attachments included — until a manual
    // refresh.
    function applyAnnouncementNotificationPatch(n) {
        const items = document.querySelectorAll(
            `.header-notif-item[data-id="${n.id}"][data-source="announcement"], .notif[data-id="${n.id}"][data-source="announcement"]`
        );

        items.forEach(function (item) {
            item.dataset.title = n.title;
            item.dataset.details = n.details;
            item.dataset.category = n.category;
            item.dataset.priority = n.priority;
            item.dataset.audience = n.audience;
            item.dataset.publish = n.publish;
            item.dataset.attachments = JSON.stringify(n.attachments || []);

            const titleEl = item.querySelector('.header-notif-title');
            if (titleEl) titleEl.textContent = n.title;

            const descEl = item.querySelector('.header-notif-desc');
            if (descEl) descEl.textContent = n.details;

            const iconWrap = item.querySelector('.header-notif-icon-wrap');
            if (iconWrap) {
                const attachments = n.attachments || [];
                const first = attachments[0] || null;
                const isImage = !!(first && String(first.mime_type || '').toLowerCase().startsWith('image/'));

                iconWrap.classList.toggle('header-notif-icon-thumb', isImage);
                iconWrap.innerHTML = isImage
                    ? `<img src="${first.file_path}" alt="" class="header-notif-thumb-img" loading="lazy">`
                    : '<i class="fa-regular fa-bell"></i>';

                if (first) {
                    const badge = document.createElement('span');
                    badge.className = 'header-notif-attach-badge';
                    badge.title = 'Has attachment';
                    badge.innerHTML = '<i class="fa-solid fa-paperclip"></i>';
                    iconWrap.appendChild(badge);
                }
            }
        });

        // If the notification the user just edited is still open in the
        // view modal, refresh it too instead of leaving stale data visible.
        const viewModal = document.getElementById('viewNotificationsModal');
        if (viewModal && viewModal.style.display === 'flex' && Number(viewModal.dataset.notificationId || 0) === n.id) {
            const sourceItem = document.querySelector(`.header-notif-item[data-id="${n.id}"][data-source="announcement"]`);
            if (sourceItem && typeof window.openNotificationModal === 'function') {
                window.openNotificationModal(sourceItem);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        const editId = Number(params.get('edit_announcement') || 0);
        if (editId > 0) {
            openAnnouncementEditModal(editId);
        }

        // Only the edit flow is intercepted — saving posts via fetch so a
        // success/error toast shows immediately, with no page reload
        // required. Create still submits normally (full page flow).
        const announcementFormEl = document.getElementById('announcementForm');
        if (announcementFormEl) {
            announcementFormEl.addEventListener('submit', async function (event) {
                if (!announcementFormEl.dataset.editingId) {
                    return; // create mode: let the normal form POST + redirect flow happen
                }

                event.preventDefault();

                const submitBtn = document.getElementById('announcementSubmitBtn');
                const originalLabel = submitBtn ? submitBtn.textContent : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Saving...';
                }

                try {
                    const response = await fetch(announcementFormEl.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(announcementFormEl),
                    });
                    const result = await response.json();

                    if (!response.ok || !result || result.success !== true) {
                        showToast((result && result.message) || 'Failed to update announcement. Please try again.', 'error');
                        return;
                    }

                    showToast(result.message || 'Announcement updated successfully.', 'success');
                    if (result.notification) {
                        applyAnnouncementNotificationPatch(result.notification);
                    }
                    closeAnnouncementForm();
                } catch (error) {
                    console.error('Unable to update announcement.', error);
                    showToast('Unable to update announcement. Please check your connection and try again.', 'error');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalLabel;
                    }
                }
            });
        }
    });

    function toggleAnnouncementAudience() {
        const audience = document.getElementById("announcementAudience").value;
        const wrap = document.getElementById("announcementDepartmentWrap");
        const input = document.getElementById("announcementDepartment");

        if (audience === "department") {
            wrap.style.display = "block";
            input.required = true;
        } else {
            wrap.style.display = "none";
            input.required = false;
            input.value = "";
        }
    }

    

    function toggleNotificationsGroup(target = "published") {
        const tabPublished = document.getElementById("tabPublished");
        const tabUnpublished = document.getElementById("tabUnpublished");
        const publishedGroup = document.getElementById("publishedGroup");
        const unpublishedGroup = document.getElementById("unpublishedGroup");

        if (!publishedGroup) return;

        if (target === "unpublished" && unpublishedGroup) {
            publishedGroup.classList.remove("active");
            unpublishedGroup.classList.add("active");
            if (tabPublished) tabPublished.classList.remove("active");
            if (tabUnpublished) tabUnpublished.classList.add("active");
            return;
        }

        publishedGroup.classList.add("active");
        if (unpublishedGroup) {
            unpublishedGroup.classList.remove("active");
        }
        if (tabPublished) tabPublished.classList.add("active");
        if (tabUnpublished) tabUnpublished.classList.remove("active");
    }

    async function clearNotificationsView() {
        if (!window.notificationDismissedUrl || !window.notificationCsrfTokenName || !window.notificationCsrfTokenHash) {
            return;
        }

        if (!window.confirm('Clear all notifications from your account?')) {
            return;
        }

        const payload = new URLSearchParams();
        payload.append('dismiss_all', '1');
        payload.append(window.notificationCsrfTokenName, window.notificationCsrfTokenHash);

        try {
            const response = await fetch(window.notificationDismissedUrl, {
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
                return;
            }

            window.location.reload();
        } catch (error) {
            console.error('Unable to clear notifications for current user.', error);
        }
    }

    document.getElementById('viewNotificationsModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeNotificationModal();
        }
    });

    
    toggleNotificationsGroup("published");

    /* CLOCK SYSTEM */

    const SHIFT_MINUTES = <?= (int) ($shiftMinutes ?? 480) ?>;
    const BREAK_MINUTES = <?= max(1, (int) ($nextBreakMinutes ?? 60)) ?>;

    let shiftRemaining = SHIFT_MINUTES;
    let breakRemaining = 0;
    let onBreak = false;
    let isClockedIn = false;

    // logic 
    if (attendance) {
        isClockedIn = true;

        // time_in is now a full datetime string e.g. "2026-06-02 09:00:00"
        // Replace space with T so all browsers parse it correctly
        const timeIn = new Date(attendance.time_in.replace(' ', 'T'));

        const now = new Date();
        let workedMinutes = Math.floor((now - timeIn) / 60000);

        // total break already taken (in minutes)
        let breakUsed = attendance.break_duration ? attendance.break_duration * 60 : 0;

        // Remaining shift
        shiftRemaining = SHIFT_MINUTES - (workedMinutes - breakUsed);

        // if currently on break — break_start is cleared back to null each time a
        // break ends, so its mere presence means "on break right now" (break_end
        // may still hold a stale value from an earlier break today, so don't gate on it)
        if (attendance.break_start) {
            onBreak = true;

            // break_start is also a full datetime string now
            const breakStart = new Date(attendance.break_start.replace(' ', 'T'));

            let breakElapsed = Math.floor((now - breakStart) / 60000);

            // subtract already taken portion of break
            breakRemaining = Math.max(BREAK_MINUTES - breakElapsed, 0);
        } else {
            // Not on break: show remaining break time
            breakRemaining = BREAK_MINUTES;
        }
    }

    const ring = document.getElementById("progressRing");
    const dot = document.getElementById("progressDot");
    const shiftTimeEl = document.getElementById("shiftTime");
    const statusBadge = document.getElementById("statusBadge");
    const clockBtn = document.getElementById("clockBtn");
    const breakBtn = document.getElementById("breakBtn");

    if (isClockedIn) {
        statusBadge.innerText = onBreak ? "On Break" : "Clocked In";
    }

    const circumference = 565.48;

    function updateDisplay(){

        let progress;

        if(onBreak){
            let safeBreak = Math.max(breakRemaining, 0);
            progress = safeBreak / BREAK_MINUTES;
            ring.setAttribute("stroke","#f97316");
        }else{
            let safeShift = Math.max(shiftRemaining, 0);
            progress = safeShift / SHIFT_MINUTES;
            ring.setAttribute("stroke","#B7E824");
        }

        if(progress<0)progress=0;
        if(progress>1)progress=1;

        let dash = progress * circumference;

        ring.setAttribute("stroke-dasharray",dash+" "+circumference);

        let angle = progress * 360;
        let rad = angle * Math.PI / 180;

        let x = 100 + 90 * Math.cos(rad);
        let y = 100 + 90 * Math.sin(rad);

        dot.setAttribute("cx",x);
        dot.setAttribute("cy",y);

        if(onBreak){
            let safeBreak = Math.max(breakRemaining, 0);
            shiftTimeEl.innerText = "Break "+Math.ceil(safeBreak)+"m";
        }else{
            let safeShift = Math.max(shiftRemaining, 0);
            let hours = Math.floor(safeShift/60);
            let minutes = Math.floor(safeShift%60);
            shiftTimeEl.innerText = hours+":"+minutes.toString().padStart(2,'0');
        }

    }

    setInterval(()=>{

        if(!isClockedIn) return;

        if(onBreak){
            if(breakRemaining > 0){
                breakRemaining--;
                updateDisplay();
                if(breakRemaining <= 0){
                    // Reaching zero does not end a break; only the server-confirmed End Break action does.
                    updateDisplay();
                }
            }
        }else{
            if(shiftRemaining > 0){
                shiftRemaining--;
                updateDisplay();
            }
        }

    },60000);

    const now = new Date();

    const days=["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
    const months=["January","February","March","April","May","June","July","August","September","October","November","December"];

    document.getElementById("clockDate").innerText =
        days[now.getDay()] + ", " +
        months[now.getMonth()] + " " +
        now.getDate() + ", " +
        now.getFullYear();

    updateDisplay();
    if (breakBtn) breakBtn.disabled = true;

    // Every path that finalizes a clock-out (direct, after the undertime modal,
    // after the missed-clockout modal) funnels through here so a fast double
    // click/tap can only ever submit the form once — the server already tolerates
    // a repeat submission safely, but this avoids the redundant round trip and
    // any confusing "changed in another request" flash on a genuine double-click.
    let clockOutSubmitting = false;
    function submitClockOutForm()
    {
        if (clockOutSubmitting) return;
        clockOutSubmitting = true;
        const form = document.getElementById('clockOutForm');
        form.querySelectorAll('button').forEach(btn => btn.disabled = true);
        form.submit();
    }

    function handleClockOut()
    {
        // Clocking out on a later calendar day than the shift started means this
        // is an overdue/missed clock-out (e.g. forgot to clock out yesterday),
        // not an early clock-out — the scheduled-end comparison below assumes
        // "today", so it would otherwise misreport this as clocking out early.
        if (attendance && attendance.time_in)
        {
            const shiftDate = (attendance.scheduled_end_datetime || attendance.time_in).slice(0, 10);

            const localNow = new Date();
            const todayDate =
                localNow.getFullYear() + '-' +
                String(localNow.getMonth() + 1).padStart(2, '0') + '-' +
                String(localNow.getDate()).padStart(2, '0');

            if (todayDate > shiftDate)
            {
                document
                    .getElementById(
                        'missedClockoutModal'
                    )
                    .style.display =
                    'flex';

                return;
            }
        }

        if (!scheduledEnd)
        {
            submitClockOutForm();

            return;
        }

        const now = new Date();

        const hh =
            String(now.getHours())
                .padStart(2, '0');

        const mm =
            String(now.getMinutes())
                .padStart(2, '0');

        const ss =
            String(now.getSeconds())
                .padStart(2, '0');

        const current =
            `${hh}:${mm}:${ss}`;

        const currentDate = new Date();
        const [h,m,s] =
            scheduledEnd.split(':');

        const scheduled = attendance.scheduled_end_datetime
            ? new Date(attendance.scheduled_end_datetime.replace(' ', 'T'))
            : new Date();

        if (!attendance.scheduled_end_datetime) scheduled.setHours(
            parseInt(h),
            parseInt(m),
            parseInt(s || 0),
            0
        );

        if (currentDate < scheduled)
        {
            document
                .getElementById(
                    'undertimeModal'
                )
                .style.display =
                'flex';

            return;
        }

        submitClockOutForm();
    }

    function proceedClockOut()
    {
        const hasApprovedUT =
            <?= $hasApprovedUT ? 'true' : 'false' ?>;

        const textarea =
            document.getElementById(
                'earlyClockOutReason'
            );

        const reason =
            textarea
                ? textarea.value.trim()
                : '';

        if (
            !hasApprovedUT &&
            !reason
        ) {
            showToast(
                'Please enter a reason.', 'warning'
            );
            return;
        }

        document.getElementById(
            'unapproved_ut_reason'
        ).value = reason;

        submitClockOutForm();
    }

    function closeUndertimeModal()
    {
        document.getElementById('undertimeModal').style.display = 'none';

        const textarea =
            document.getElementById('earlyClockOutReason');

        if (textarea) {
            textarea.value = '';
        }
    }

    function proceedMissedClockout()
    {
        const textarea =
            document.getElementById(
                'missedClockoutReason'
            );

        const reason =
            textarea
                ? textarea.value.trim()
                : '';

        if (!reason) {
            showToast(
                'Please enter a reason.', 'warning'
            );
            return;
        }

        document.getElementById(
            'missed_clockout_reason'
        ).value = reason;

        submitClockOutForm();
    }

    function closeMissedClockoutModal()
    {
        document.getElementById('missedClockoutModal').style.display = 'none';

        const textarea =
            document.getElementById('missedClockoutReason');

        if (textarea) {
            textarea.value = '';
        }
    }

    <?php $otPrompt = session()->getFlashdata('prompt_ot_request'); ?>
    <?php if ($otPrompt): ?>
    openLateOvertimeRequest(
        <?= json_encode($otPrompt['date']) ?>,
        <?= json_encode($otPrompt['reason']) ?>
    );
    <?php endif; ?>

</script>
<script src="<?= base_url('js/components/task-log.js') ?>"></script>

<script>
    // Preview and removal helpers for announcement attachments
    let _announcementSelectedFiles = [];

    function handleAnnouncementAttachments(filesArray) {
        const list = document.getElementById('announcementAttachmentsList');
        if (!list) return;
        list.innerHTML = '';
        if (!filesArray || filesArray.length === 0) return;
        const files = Array.from(filesArray);
        files.forEach((file, i) => {
            const item = document.createElement('div');
            item.className = 'attachment-item';

            const preview = document.createElement('div');
            preview.className = 'attachment-preview';

            if (file.type && file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.onload = () => URL.revokeObjectURL(img.src);
                preview.appendChild(img);
            } else {
                const span = document.createElement('span');
                span.textContent = file.name;
                preview.appendChild(span);
            }

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'cancel-btn';
            removeBtn.textContent = 'Remove';
            removeBtn.addEventListener('click', function () {
                removeAnnouncementAttachment(i);
            });

            item.appendChild(preview);
            item.appendChild(removeBtn);
            list.appendChild(item);
        });
    }

    function removeAnnouncementAttachment(index) {
        const input = document.getElementById('announcementAttachments');
        if (!input) return;
        // remove from our array
        if (index >= 0 && index < _announcementSelectedFiles.length) {
            _announcementSelectedFiles.splice(index, 1);
        }
        // sync back to input.files
        const dt = new DataTransfer();
        _announcementSelectedFiles.forEach(f => dt.items.add(f));
        input.files = dt.files;
        handleAnnouncementAttachments(_announcementSelectedFiles);
        updateNameFromArray(_announcementSelectedFiles);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('announcementAttachments');
        const btn = document.getElementById('file-upload-button');
        const nameEl = document.getElementById('announcementAttachmentsName');

        function updateNameFromArray(arr){
            if (!nameEl) return;
            if (!arr || arr.length === 0) {
                nameEl.textContent = 'No files chosen';
                return;
            }
            if (arr.length === 1) {
                nameEl.textContent = arr[0].name;
            } else {
                nameEl.textContent = arr.length + ' files chosen';
            }
        }

        if (el) {
            el.addEventListener('change', function (e) {
                const added = Array.from(e.target.files || []);
                // merge new files into our array (avoid duplicates by name+size+type)
                added.forEach(f => {
                    const exists = _announcementSelectedFiles.some(existing =>
                        existing.name === f.name && existing.size === f.size && existing.type === f.type
                    );
                    if (!exists) _announcementSelectedFiles.push(f);
                });

                // sync back to the hidden input
                const dt = new DataTransfer();
                _announcementSelectedFiles.forEach(f => dt.items.add(f));
                el.files = dt.files;

                handleAnnouncementAttachments(_announcementSelectedFiles);
                updateNameFromArray(_announcementSelectedFiles);
            });
        }

        if (btn && el) {
            btn.addEventListener('click', function () { el.click(); });
        }
    });
</script>

<script>
  const dropzone = document.getElementById('dropzone');
  const fileInput = document.getElementById('fileInput');
  const attachList = document.getElementById('attachList');
  let files = [];
 
  dropzone.addEventListener('click', () => fileInput.click());
 
  ['dragenter', 'dragover'].forEach(evt =>
    dropzone.addEventListener(evt, e => {
      e.preventDefault();
      dropzone.classList.add('dragover');
    })
  );
  ['dragleave', 'drop'].forEach(evt =>
    dropzone.addEventListener(evt, e => {
      e.preventDefault();
      dropzone.classList.remove('dragover');
    })
  );
  dropzone.addEventListener('drop', e => {
    addFiles(e.dataTransfer.files);
  });
 
  fileInput.addEventListener('change', () => {
    // Capture the picked files and clear the native input's value FIRST
    // (so selecting the same file again still fires another 'change'
    // event) — addFiles() below rebuilds fileInput.files from the full
    // accumulated list via syncFileInput(), which must happen after this
    // clear or it gets wiped right back out.
    const picked = Array.from(fileInput.files);
    fileInput.value = '';
    addFiles(picked);
  });
 
  function addFiles(fileListObj) {
    Array.from(fileListObj).forEach(f => files.push(f));
    syncFileInput();
    render();
  }

  function removeFile(index) {
    files.splice(index, 1);
    syncFileInput();
    render();
  }

  // fileInput.value is reset to '' on every 'change' (so re-picking the same
  // file still fires another change event), which also wipes its native
  // .files FileList — without this, the files shown in the preview list were
  // never actually part of the form submission, so nothing got uploaded.
  function syncFileInput() {
    const dt = new DataTransfer();
    files.forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
  }
 
  function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }
 
  function iconFor(file) {
    if (file.type === 'application/pdf') {
      return { cls: 'pdf', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>` };
    }
    if (file.type.includes('word') || file.name.match(/\.docx?$/i)) {
      return { cls: 'doc', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>` };
    }
    return { cls: '', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>` };
  }
 
  function render() {
    attachList.innerHTML = '';
    files.forEach((file, i) => {
      const row = document.createElement('div');
      row.className = 'attach-row';
 
      let mediaHtml;
      if (file.type.startsWith('image/')) {
        const url = URL.createObjectURL(file);
        mediaHtml = `<img class="thumb" src="${url}" alt="">`;
      } else {
        const ic = iconFor(file);
        mediaHtml = `<div class="file-icon ${ic.cls}">${ic.svg}</div>`;
      }
 
      row.innerHTML = `
        ${mediaHtml}
        <div class="meta">
          <div class="name">${file.name}</div>
          <div class="size">${formatSize(file.size)}</div>
        </div>
        <button type="button" class="remove-btn" data-index="${i}" aria-label="Remove file">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      `;
      attachList.appendChild(row);
    });
 
    attachList.querySelectorAll('.remove-btn').forEach(btn => {
      btn.addEventListener('click', () => removeFile(Number(btn.dataset.index)));
    });
  }
</script>

<?= $this->endSection() ?>
