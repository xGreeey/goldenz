<?php
$page_title = 'Hiring Handbook - Golden Z-5 HR System';
$page = 'handbook';

// Handbook sections
$handbook_sections = [
    'company_overview' => [
        'title' => 'Company Overview',
        'icon' => 'fas fa-building',
        'content' => [
            'mission' => 'Mission Statement',
            'vision' => 'Vision Statement',
            'values' => 'Core Values',
            'history' => 'Company History',
            'structure' => 'Organizational Structure'
        ]
    ],
    'employment_policies' => [
        'title' => 'Employment Policies',
        'icon' => 'fas fa-file-contract',
        'content' => [
            'equal_opportunity' => 'Equal Opportunity Employment',
            'harassment' => 'Anti-Harassment Policy',
            'discrimination' => 'Non-Discrimination Policy',
            'workplace_safety' => 'Workplace Safety',
            'confidentiality' => 'Confidentiality Agreement'
        ]
    ],
    'hiring_process' => [
        'title' => 'Hiring Process',
        'icon' => 'fas fa-user-plus',
        'content' => [
            'job_posting' => 'Job Posting Guidelines',
            'application_review' => 'Application Review Process',
            'interview_process' => 'Interview Process',
            'background_check' => 'Background Check Procedures',
            'selection_criteria' => 'Selection Criteria'
        ]
    ],
    'compensation_benefits' => [
        'title' => 'Compensation & Benefits',
        'icon' => 'fas fa-money-bill-wave',
        'content' => [
            'salary_structure' => 'Salary Structure',
            'benefits_package' => 'Benefits Package',
            'overtime_policy' => 'Overtime Policy',
            'bonus_system' => 'Bonus System',
            'retirement_plan' => 'Retirement Plan'
        ]
    ]
];

// Sample content
$handbook_content = [
    'mission' => 'To provide exceptional security services while maintaining the highest standards of professionalism, integrity, and customer satisfaction.',
    'vision' => 'To be the leading security and investigation agency in the Philippines, recognized for excellence and innovation.',
    'values' => 'Integrity, Professionalism, Excellence, Teamwork, and Customer Focus.',
    'history' => 'Golden Z-5 Security and Investigation Agency was established in 2010 with a vision to provide comprehensive security solutions.',
    'structure' => 'Our organization is structured with clear reporting lines and defined roles for effective service delivery.',
    
    'equal_opportunity' => 'We are committed to providing equal employment opportunities to all qualified individuals regardless of race, color, religion, sex, national origin, age, disability, or veteran status.',
    'harassment' => 'We maintain a zero-tolerance policy for harassment of any kind. All employees have the right to work in an environment free from harassment.',
    'discrimination' => 'Discrimination based on protected characteristics is strictly prohibited and will not be tolerated.',
    'workplace_safety' => 'Employee safety is our top priority. All employees must follow safety protocols and report any unsafe conditions immediately.',
    'confidentiality' => 'All employees must maintain strict confidentiality regarding client information and company operations.',
    
    'job_posting' => 'Job postings must be clear, accurate, and comply with all applicable laws and regulations.',
    'application_review' => 'All applications are reviewed fairly and consistently based on job requirements and qualifications.',
    'interview_process' => 'Interviews are conducted in a professional manner with multiple interviewers when possible.',
    'background_check' => 'All candidates undergo thorough background checks before employment.',
    'selection_criteria' => 'Selection is based on qualifications, experience, skills, and cultural fit.',
    
    'salary_structure' => 'Our salary structure is competitive and based on market rates, experience, and performance.',
    'benefits_package' => 'We offer comprehensive benefits including health insurance, retirement plans, and paid time off.',
    'overtime_policy' => 'Overtime is compensated at 1.5 times the regular rate for hours worked beyond 40 per week.',
    'bonus_system' => 'Performance-based bonuses are awarded quarterly based on individual and company performance.',
    'retirement_plan' => 'We offer a 401(k) retirement plan with company matching contributions.'
];
?>

<div class="handbook-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <h1>Hiring Handbook</h1>
            <p class="text-muted">Company policies, procedures, and guidelines for employees and management</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-outline-primary" id="printHandbookBtn">
                <i class="fas fa-print me-2"></i>Print Handbook
            </button>
            <button class="btn btn-primary" id="editHandbookBtn">
                <i class="fas fa-edit me-2"></i>Edit Content
            </button>
        </div>
    </div>

    <!-- Search and Navigation -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" class="form-control" id="handbookSearch" placeholder="Search handbook content...">
            </div>
        </div>
        <div class="col-md-4">
            <select class="form-select" id="sectionFilter">
                <option value="">All Sections</option>
                <?php foreach ($handbook_sections as $key => $section): ?>
                <option value="<?php echo $key; ?>"><?php echo $section['title']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Handbook Navigation -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Handbook Sections</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($handbook_sections as $key => $section): ?>
                <div class="col-md-3 mb-3">
                    <div class="handbook-section-card" data-section="<?php echo $key; ?>">
                        <div class="section-icon">
                            <i class="<?php echo $section['icon']; ?>"></i>
                        </div>
                        <h6 class="section-title"><?php echo $section['title']; ?></h6>
                        <small class="text-muted"><?php echo count($section['content']); ?> topics</small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Handbook Content -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0" id="currentSectionTitle">Select a section to view content</h5>
        </div>
        <div class="card-body" id="handbookContent">
            <div class="text-center py-5">
                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Welcome to the Golden Z-5 Employee Handbook</h5>
                <p class="text-muted">Select a section from the navigation above to view detailed information about our policies and procedures.</p>
            </div>
        </div>
    </div>
</div>

<style>
.handbook-section-card {
    padding: 1.5rem;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: #f8f9fa;
}

.handbook-section-card:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.section-icon {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.handbook-section-card:hover .section-icon {
    color: white;
}

.section-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.handbook-content {
    max-height: 600px;
    overflow-y: auto;
}

.content-section {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.content-section:last-child {
    border-bottom: none;
}

.content-section h6 {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.content-section p {
    line-height: 1.6;
    margin-bottom: 0.5rem;
}
/* Card styling to match HR admin dashboard */
.card-modern,
.card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    background: #ffffff;
    overflow: hidden;
    transition: all 0.3s ease;
    outline: none !important;
}

.card-modern:hover,
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0 !important;
    outline: none !important;
}

.card-modern:focus,
.card:focus,
.card-modern:focus-visible,
.card:focus-visible {
    outline: none !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0 !important;
}

.card-body {
    padding: 1.5rem;
}

.card-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.card-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.25rem 0;
}

/* Dark theme support for handbook page */
html[data-theme="dark"] .handbook-container {
    background-color: transparent;
    color: var(--interface-text);
}

html[data-theme="dark"] .handbook-section-card {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .handbook-section-card .section-title {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .handbook-section-card .text-muted {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .handbook-section-card .section-icon {
    color: var(--primary-color) !important;
}

html[data-theme="dark"] .handbook-section-card:hover {
    background: var(--primary-color) !important;
    color: white !important;
}

html[data-theme="dark"] .handbook-section-card:hover .section-icon {
    color: white !important;
}

html[data-theme="dark"] .handbook-section-card:hover .text-muted {
    color: rgba(255, 255, 255, 0.8) !important;
}

html[data-theme="dark"] .content-section {
    border-bottom-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .content-section h6 {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .content-section p {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .card-title {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .card-header {
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .card-body {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .handbook-content {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .handbook-content ul {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .handbook-content li {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .handbook-content strong {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .page-header {
    background-color: #1a1d23 !important;
    border: 1px solid var(--interface-border) !important;
    border-radius: 14px; /* Rounded rectangle */
    padding: 1.5rem 2rem; /* Adjusted padding */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04); /* Added shadow */
    color: var(--interface-text) !important;
    margin-bottom: var(--spacing-xl) !important;
}

html[data-theme="dark"] .page-header h1 {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .page-header .text-muted {
    color: var(--interface-text-muted) !important;
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const handbookManager = new HandbookManager();
    handbookManager.init();
});

class HandbookManager {
    constructor() {
        this.handbookSections = <?php echo json_encode($handbook_sections); ?>;
        this.handbookContent = <?php echo json_encode($handbook_content); ?>;
        this.currentSection = null;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Section navigation
        document.addEventListener('click', (e) => {
            if (e.target.closest('.handbook-section-card')) {
                const section = e.target.closest('.handbook-section-card').dataset.section;
                this.showSection(section);
            }
        });

        // Search functionality
        document.getElementById('handbookSearch').addEventListener('input', (e) => {
            this.searchContent(e.target.value);
        });

        // Section filter
        document.getElementById('sectionFilter').addEventListener('change', (e) => {
            this.filterSections(e.target.value);
        });

        // Action buttons
        document.getElementById('printHandbookBtn').addEventListener('click', () => {
            this.printHandbook();
        });

        document.getElementById('editHandbookBtn').addEventListener('click', () => {
            this.editHandbook();
        });
    }

    showSection(sectionKey) {
        this.currentSection = sectionKey;
        const section = this.handbookSections[sectionKey];
        
        document.getElementById('currentSectionTitle').textContent = section.title;
        
        let content = `
            <div class="handbook-content">
                <div class="content-section">
                    <h6>${section.title}</h6>
                    <p>This section covers the following topics:</p>
                    <ul>
        `;
        
        Object.keys(section.content).forEach(itemKey => {
            const itemTitle = section.content[itemKey];
            const itemContent = this.handbookContent[itemKey] || 'Content not available.';
            
            content += `
                <li>
                    <strong>${itemTitle}</strong>
                    <div class="mt-2 mb-3">
                        <p>${itemContent}</p>
                    </div>
                </li>
            `;
        });
        
        content += `
                    </ul>
                </div>
            </div>
        `;
        
        document.getElementById('handbookContent').innerHTML = content;
    }

    searchContent(searchTerm) {
        if (!searchTerm) {
            this.clearSearchHighlights();
            return;
        }
        
        const content = document.getElementById('handbookContent');
        const text = content.textContent;
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        
        if (regex.test(text)) {
            content.innerHTML = content.innerHTML.replace(regex, '<span class="search-highlight">$1</span>');
        }
    }

    clearSearchHighlights() {
        const highlights = document.querySelectorAll('.search-highlight');
        highlights.forEach(highlight => {
            highlight.outerHTML = highlight.innerHTML;
        });
    }

    filterSections(filter) {
        const cards = document.querySelectorAll('.handbook-section-card');
        cards.forEach(card => {
            if (!filter || card.dataset.section === filter) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    printHandbook() {
        window.print();
    }

    editHandbook() {
        alert('Edit functionality will be implemented in the next version.');
    }
}
</script>