@extends('layouts.app')

@section('content')

<div class="page-header">
  <h1 class="page-title">Files</h1>
  <p class="page-subtitle">Uploaded files are automatically removed after 24 hours.</p>
</div>

<div class="card mb-4">
  <div class="card-inner">
    <div id="alertArea"></div>

    <div id="dropZone">
      <div class="dropzone-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
          <polyline points="16 16 12 12 8 16"/>
          <line x1="12" y1="12" x2="12" y2="21"/>
          <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>
        </svg>
      </div>
      <p class="dropzone-primary">Drop a file here or click to browse</p>
      <p class="dropzone-secondary">PDF or DOCX &nbsp;·&nbsp; max 10 MB</p>
      <input type="file" id="fileInput" accept=".pdf,.docx" class="visually-hidden">
    </div>

    <div id="progressWrap" class="progress-wrap" aria-hidden="true">
      <div class="progress-track">
        <div id="progressBar" class="progress-fill"></div>
      </div>
      <p class="progress-label" id="progressLabel">Uploading…</p>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-inner">

    @if($files->isEmpty())
      <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
        </svg>
        <p class="empty-primary">No files uploaded yet</p>
        <p class="empty-secondary">Uploaded files will appear here</p>
      </div>
    @else
      <table class="files-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Size</th>
            <th>Uploaded</th>
            <th>Expires</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($files as $file)
            @php
              $expiresAt = $file->uploaded_at->addHours(24);
              $minutesLeft = now()->diffInMinutes($expiresAt, false);
              $expiryClass = $minutesLeft < 60 ? 'badge-danger' : 'badge-warning';
            @endphp
            <tr id="file-row-{{ $file->id }}">
              <td class="col-name">
                <span class="filename">{{ $file->original_name }}</span>
              </td>
              <td>
                @if(str_contains($file->mime_type, 'pdf'))
                  <span class="badge badge-pdf">PDF</span>
                @else
                  <span class="badge badge-docx">DOCX</span>
                @endif
              </td>
              <td class="col-meta">{{ $file->human_size }}</td>
              <td class="col-meta">{{ $file->uploaded_at->format('d M, H:i') }}</td>
              <td>
                <span class="badge {{ $expiryClass }}">{{ $expiresAt->format('d M, H:i') }}</span>
              </td>
              <td class="col-action">
                <button class="btn-delete" data-id="{{ $file->id }}" data-name="{{ $file->original_name }}">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    <path d="M10 11v6M14 11v6"/>
                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                  </svg>
                  Delete
                </button>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

      @if($files->hasPages())
        <div class="pagination-wrap">
          {{ $files->links() }}
        </div>
      @endif
    @endif
  </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content app-modal">
      <div class="app-modal-header">
        <span class="app-modal-title">Delete file</span>
        <button type="button" class="app-modal-close" data-bs-dismiss="modal">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
      <div class="app-modal-body">
        <p>Are you sure you want to delete <strong id="deleteFileName" class="filename"></strong>?</p>
        <p class="modal-hint">This file will be permanently removed and a notification will be sent.</p>
      </div>
      <div class="app-modal-footer">
        <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn-danger-solid" id="confirmDeleteBtn">Delete file</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  const $drop = $('#dropZone');
  const $input = $('#fileInput');
  const $progressWrap = $('#progressWrap');
  const $progressBar = $('#progressBar');
  const $progressLabel = $('#progressLabel');

  $drop.on('click', () => $input[0]?.click());
  $drop.on('dragover', e => { e.preventDefault(); $drop.addClass('dragover'); });
  $drop.on('dragleave drop', () => $drop.removeClass('dragover'));
  $drop.on('drop', e => {
    e.preventDefault();
    const f = e.originalEvent.dataTransfer.files[0];
    if (f) uploadFile(f);
  });

  $input.on('change', function () {
    if (this.files[0]) uploadFile(this.files[0]);
    $(this).val('');
  });

  function setProgressVisible(visible) {
    $progressWrap.toggleClass('is-hidden', !visible).attr('aria-hidden', visible ? 'false' : 'true');
  }

  function uploadFile(file) {
    const fd = new FormData();
    fd.append('file', file);

    setProgressVisible(true);
    $progressBar.css('width', '0%');
    $progressLabel.text('Uploading…');

    $.ajax({
      url: '{{ route("files.upload") }}',
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      xhr: function () {
        const xhr = new window.XMLHttpRequest();
        xhr.upload.addEventListener('progress', function (e) {
          if (e.lengthComputable) {
            const pct = Math.round((e.loaded / e.total) * 100);
            $progressBar.css('width', pct + '%');
            $progressLabel.text(pct < 100 ? 'Uploading… ' + pct + '%' : 'Processing…');
          }
        });
        return xhr;
      },
      success: function (res) {
        setProgressVisible(false);
        showAlert('success', '"' + res.file.original_name + '" uploaded successfully.');
        setTimeout(() => location.reload(), 1000);
      },
      error: function (xhr) {
        setProgressVisible(false);
        const json = xhr.responseJSON;
        const msg = json?.errors?.file?.[0] || json?.message || 'Upload failed.';
        showAlert('error', msg);
      }
    });
  }

  let deleteModalInstance = null;
  const modalEl = document.getElementById('deleteModal');
  if (modalEl && window.bootstrap) {
    deleteModalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
  }

  $(document).on('click', '.btn-delete', function () {
    const $btn = $(this);
    $('#deleteFileName').text($btn.data('name'));
    $('#confirmDeleteBtn').data('id', $btn.data('id'));
    if (deleteModalInstance) deleteModalInstance.show();
  });

  $('#confirmDeleteBtn').on('click', function () {
    const id = $(this).data('id');
    $.ajax({
      url: '/files/' + id,
      type: 'DELETE',
      success: function () {
        if (deleteModalInstance) deleteModalInstance.hide();

        const $row = $('#file-row-' + id);
        $row.addClass('removing');
        setTimeout(() => $row.remove(), 260);

        showAlert('success', 'File deleted.');
      },
      error: function () {
        if (deleteModalInstance) deleteModalInstance.hide();
        showAlert('error', 'Could not delete the file.');
      }
    });
  });

  function showAlert(type, msg) {
    const $a = $('<div class="app-alert app-alert-' + type + '">' + msg + '</div>');
    $('#alertArea').html($a);
    setTimeout(() => $a.addClass('is-dismissing'), 4200);
    setTimeout(() => $a.remove(), 4600);
  }
});
</script>
@endpush
