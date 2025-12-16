<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Page Number Fixer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .upload-zone {
            border: 3px dashed #667eea;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9ff;
        }
        .upload-zone:hover {
            border-color: #764ba2;
            background: #f0f2ff;
        }
        .upload-zone.dragover {
            border-color: #28a745;
            background: #d4edda;
        }
        .analysis-result {
            display: none;
            margin-top: 20px;
        }
        .issue-card {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success-card {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
        .progress-container {
            display: none;
            margin-top: 15px;
        }
        .action-buttons {
            display: none;
            margin-top: 20px;
        }
        .section-info {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="main-card">
                    <div class="card-header bg-gradient text-white p-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h2 class="mb-0">
                            <i class="fas fa-file-word"></i> Word Page Number Fixer
                        </h2>
                        <p class="mb-0 mt-2">Tự động phát hiện và sửa lỗi đánh số trang trong file Word</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Upload Zone -->
                        <div class="upload-zone" id="uploadZone">
                            <i class="fas fa-cloud-upload-alt fa-4x text-primary mb-3"></i>
                            <h4>Kéo thả file Word vào đây</h4>
                            <p class="text-muted">hoặc click để chọn file</p>
                            <p class="small text-muted">Hỗ trợ: .docx, .doc (Max: 50MB)</p>
                            <input type="file" id="fileInput" accept=".doc,.docx" hidden>
                        </div>

                        <!-- Progress -->
                        <div class="progress-container" id="progressContainer">
                            <div class="d-flex justify-content-between mb-2">
                                <span id="progressText">Đang xử lý...</span>
                                <span id="progressPercent">0%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     id="progressBar" 
                                     role="progressbar" 
                                     style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Alert Container -->
                        <div id="alertContainer" class="mt-3"></div>

                        <!-- Analysis Result -->
                        <div class="analysis-result" id="analysisResult">
                            <hr class="my-4">
                            <h4><i class="fas fa-chart-bar"></i> Kết quả phân tích</h4>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5><i class="fas fa-info-circle text-info"></i> Thông tin file</h5>
                                            <p class="mb-1"><strong>Tên file:</strong> <span id="fileName"></span></p>
                                            <p class="mb-1"><strong>Tổng số trang:</strong> <span id="totalPages"></span></p>
                                            <p class="mb-0"><strong>Số sections:</strong> <span id="totalSections"></span></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5><i class="fas fa-exclamation-triangle text-warning"></i> Vấn đề phát hiện</h5>
                                            <p class="mb-0"><strong>Sections có vấn đề:</strong> <span id="issueCount"></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Issues Detail -->
                            <div id="issuesDetail" class="mt-3"></div>

                            <!-- Sections Detail -->
                            <div class="mt-3">
                                <h5><i class="fas fa-list"></i> Chi tiết Sections</h5>
                                <div id="sectionsDetail"></div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="action-buttons" id="actionButtons">
                                <hr class="my-4">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                    <button class="btn btn-success btn-lg" id="fixBtn">
                                        <i class="fas fa-wrench"></i> Sửa lỗi Page Numbering
                                    </button>
                                    <button class="btn btn-primary btn-lg" id="downloadBtn" style="display:none;">
                                        <i class="fas fa-download"></i> Tải file đã sửa
                                    </button>
                                    <button class="btn btn-secondary btn-lg" id="resetBtn">
                                        <i class="fas fa-redo"></i> Upload file khác
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card mt-4 main-card">
                    <div class="card-body">
                        <h5><i class="fas fa-question-circle"></i> Hướng dẫn sử dụng</h5>
                        <ol>
                            <li>Upload file Word (.docx hoặc .doc)</li>
                            <li>Hệ thống sẽ tự động phân tích và phát hiện lỗi đánh số trang</li>
                            <li>Nếu có lỗi, click nút "Sửa lỗi Page Numbering"</li>
                            <li>Tải file đã sửa về máy</li>
                            <li>File sẽ tự động xóa sau khi tải về</li>
                        </ol>
                        <p class="text-muted small mb-0">
                            <i class="fas fa-shield-alt"></i> 
                            File của bạn được xử lý an toàn và tự động xóa sau khi hoàn tất.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const progressPercent = document.getElementById('progressPercent');
        const alertContainer = document.getElementById('alertContainer');
        const analysisResult = document.getElementById('analysisResult');
        const actionButtons = document.getElementById('actionButtons');
        const fixBtn = document.getElementById('fixBtn');
        const downloadBtn = document.getElementById('downloadBtn');
        const resetBtn = document.getElementById('resetBtn');

        let currentFileId = null;

        // Event Listeners
        uploadZone.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', (e) => handleFile(e.target.files[0]));
        
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                handleFile(e.dataTransfer.files[0]);
            }
        });

        fixBtn.addEventListener('click', fixDocument);
        downloadBtn.addEventListener('click', downloadFile);
        resetBtn.addEventListener('click', resetPage);

        // Handle file upload and analysis
        function handleFile(file) {
            if (!file) return;

            // Validate file
            const ext = file.name.split('.').pop().toLowerCase();
            if (ext !== 'doc' && ext !== 'docx') {
                showAlert('Vui lòng chọn file Word (.doc hoặc .docx)', 'danger');
                return;
            }

            if (file.size > 50 * 1024 * 1024) {
                showAlert('File quá lớn. Kích thước tối đa: 50MB', 'danger');
                return;
            }

            // Upload and analyze
            uploadAndAnalyze(file);
        }

        function uploadAndAnalyze(file) {
            const formData = new FormData();
            formData.append('file', file);

            showProgress('Đang upload và phân tích file...', 30);
            analysisResult.style.display = 'none';
            actionButtons.style.display = 'none';
            clearAlerts();

            fetch('analyze.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideProgress();
                if (data.success) {
                    currentFileId = data.file_id;
                    displayAnalysis(data);
                } else {
                    showAlert(data.message || 'Lỗi phân tích file', 'danger');
                }
            })
            .catch(error => {
                hideProgress();
                showAlert('Lỗi kết nối: ' + error.message, 'danger');
            });
        }

        function displayAnalysis(data) {
            // Display file info
            document.getElementById('fileName').textContent = data.filename;
            document.getElementById('totalPages').textContent = data.total_pages || 'N/A';
            document.getElementById('totalSections').textContent = data.sections.length;
            document.getElementById('issueCount').textContent = data.issues.length;

            // Display issues
            const issuesDetail = document.getElementById('issuesDetail');
            issuesDetail.innerHTML = '';

            if (data.issues.length === 0) {
                issuesDetail.innerHTML = `
                    <div class="success-card">
                        <h5><i class="fas fa-check-circle text-success"></i> Không có vấn đề</h5>
                        <p class="mb-0">File Word của bạn không có lỗi về page numbering!</p>
                    </div>
                `;
                actionButtons.style.display = 'none';
            } else {
                data.issues.forEach(issue => {
                    issuesDetail.innerHTML += `
                        <div class="issue-card">
                            <h6><i class="fas fa-exclamation-triangle"></i> ${issue.title}</h6>
                            <p class="mb-0">${issue.description}</p>
                        </div>
                    `;
                });
                actionButtons.style.display = 'block';
                fixBtn.style.display = 'inline-block';
                downloadBtn.style.display = 'none';
            }

            // Display sections detail
            const sectionsDetail = document.getElementById('sectionsDetail');
            sectionsDetail.innerHTML = '';
            
            data.sections.forEach((section, index) => {
                const hasIssue = section.has_issue ? 'border-warning' : '';
                sectionsDetail.innerHTML += `
                    <div class="section-info ${hasIssue}">
                        <strong>Section ${index + 1}:</strong>
                        Page numbering: ${section.numbering_type}
                        ${section.start_at !== null ? ` (Start at: ${section.start_at})` : ''}
                        ${section.has_issue ? ' <span class="badge bg-warning">Có vấn đề</span>' : ' <span class="badge bg-success">OK</span>'}
                    </div>
                `;
            });

            analysisResult.style.display = 'block';
        }

        function fixDocument() {
            if (!currentFileId) return;

            showProgress('Đang sửa lỗi page numbering...', 50);
            fixBtn.disabled = true;

            fetch('fix.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ file_id: currentFileId })
            })
            .then(response => response.json())
            .then(data => {
                hideProgress();
                fixBtn.disabled = false;

                if (data.success) {
                    showAlert(`Đã sửa thành công ${data.fixed_count} section(s)! Click nút "Tải file đã sửa" để download.`, 'success');
                    fixBtn.style.display = 'none';
                    downloadBtn.style.display = 'inline-block';
                    
                    // Cập nhật lại hiển thị sau khi sửa
                    updateAfterFix(data.fixed_count);
                } else {
                    showAlert(data.message || 'Lỗi khi sửa file', 'danger');
                }
            })
            .catch(error => {
                hideProgress();
                fixBtn.disabled = false;
                showAlert('Lỗi kết nối: ' + error.message, 'danger');
            });
        }

        function updateAfterFix(fixedCount) {
            // Cập nhật issues detail - hiển thị thành công
            const issuesDetail = document.getElementById('issuesDetail');
            issuesDetail.innerHTML = `
                <div class="success-card">
                    <h5><i class="fas fa-check-circle text-success"></i> Đã sửa thành công!</h5>
                    <p class="mb-0">Đã sửa ${fixedCount} section(s). Tất cả các section bây giờ sẽ đánh số trang liên tục.</p>
                </div>
            `;

            // Cập nhật issue count
            document.getElementById('issueCount').textContent = '0 (Đã sửa)';

            // Cập nhật sections detail - đổi tất cả thành OK
            const sectionsDetail = document.getElementById('sectionsDetail');
            const sectionDivs = sectionsDetail.querySelectorAll('.section-info');
            
            sectionDivs.forEach(div => {
                // Xóa class border-warning
                div.classList.remove('border-warning');
                
                // Thay thế badge "Có vấn đề" thành "Đã sửa"
                const badge = div.querySelector('.badge');
                if (badge && badge.classList.contains('bg-warning')) {
                    badge.className = 'badge bg-success';
                    badge.textContent = 'Đã sửa';
                }
                
                // Cập nhật text về page numbering
                const text = div.innerHTML;
                if (text.includes('Start at:')) {
                    // Thay đổi text để hiển thị đã sửa thành Continue
                    div.innerHTML = text.replace(/Page numbering: Start at \d+ \(Start at: \d+\)/, 'Page numbering: Continue (Đã sửa)');
                }
            });
        }

        function downloadFile() {
            if (!currentFileId) return;

            // Open download in new window
            window.location.href = 'download.php?file_id=' + currentFileId;
            
            // Show success message
            setTimeout(() => {
                showAlert('File đã được tải về. File trên server sẽ tự động xóa.', 'info');
                // Reset after download
                setTimeout(resetPage, 3000);
            }, 1000);
        }

        function resetPage() {
            fileInput.value = '';
            analysisResult.style.display = 'none';
            actionButtons.style.display = 'none';
            clearAlerts();
            currentFileId = null;
            uploadZone.style.display = 'block';
        }

        function showProgress(text, percent) {
            progressContainer.style.display = 'block';
            progressText.textContent = text;
            progressPercent.textContent = percent + '%';
            progressBar.style.width = percent + '%';
        }

        function hideProgress() {
            progressContainer.style.display = 'none';
        }

        function showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.appendChild(alert);
            
            setTimeout(() => alert.remove(), 5000);
        }

        function clearAlerts() {
            alertContainer.innerHTML = '';
        }
    </script>
</body>
</html>