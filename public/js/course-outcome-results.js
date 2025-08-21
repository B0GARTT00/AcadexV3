let currentTerm = null;

function toggleScoreType() {
    var type = document.getElementById('scoreType').value;
    var passfailTable = document.getElementById('passfail-table');
    var copasssummaryTable = document.getElementById('copasssummary-table');
    var mainTables = document.querySelectorAll('.main-table');
    var termTables = document.querySelectorAll('.term-table');
    var summaryLabel = document.getElementById('summaryLabel');
    var termSummaryLabels = document.querySelectorAll('.term-summary-label');
    var termStepperContainer = document.getElementById('term-stepper-container');
    
    if(type === 'passfail') {
        passfailTable && (passfailTable.style.display = 'block');
        copasssummaryTable && (copasssummaryTable.style.display = 'none');
        mainTables.forEach(function(tbl) { tbl.style.display = 'none'; });
        termTables.forEach(function(tbl) { tbl.style.display = 'none'; });
        termStepperContainer && (termStepperContainer.style.display = 'block');
        
        // Hide term-specific passfail tables by default
        document.querySelectorAll('.passfail-term-table').forEach(function(tbl) { 
            tbl.style.display = 'none'; 
        });
    } else if(type === 'copasssummary') {
        passfailTable && (passfailTable.style.display = 'none');
        copasssummaryTable && (copasssummaryTable.style.display = 'block');
        mainTables.forEach(function(tbl) { tbl.style.display = 'none'; });
        termTables.forEach(function(tbl) { tbl.style.display = 'none'; });
        termStepperContainer && (termStepperContainer.style.display = 'block');
        
        // Hide term-specific summary tables by default
        document.querySelectorAll('.summary-term-table').forEach(function(tbl) { 
            tbl.style.display = 'none'; 
        });
    } else {
        passfailTable && (passfailTable.style.display = 'none');
        copasssummaryTable && (copasssummaryTable.style.display = 'none');
        termStepperContainer && (termStepperContainer.style.display = 'block');
        
        // Hide all term-specific tables when switching to scores/percentage view
        document.querySelectorAll('.passfail-term-table').forEach(function(tbl) { 
            tbl.style.display = 'none'; 
        });
        document.querySelectorAll('.summary-term-table').forEach(function(tbl) { 
            tbl.style.display = 'none'; 
        });
        
        // Show combined table by default, hide term tables
        if (!currentTerm) {
            mainTables.forEach(function(tbl) { tbl.style.display = 'block'; });
            termTables.forEach(function(tbl) { tbl.style.display = 'none'; });
        } else {
            mainTables.forEach(function(tbl) { tbl.style.display = 'none'; });
            termTables.forEach(function(tbl) { tbl.style.display = 'none'; });
            var activeTerm = document.getElementById('term-' + currentTerm);
            if (activeTerm) activeTerm.style.display = 'block';
        }
        
        document.querySelectorAll('.score-value').forEach(function(el) {
            el.style.display = 'inline';
            var score = el.getAttribute('data-score');
            var percent = el.getAttribute('data-percentage');
            
            // Remove existing color classes
            el.classList.remove('text-success', 'text-danger');
            
            if(type === 'score') {
                el.textContent = score;
            } else {
                el.textContent = percent !== '' && percent !== null ? percent + '%' : '-';
                
                // Add color coding for percentage view (only for table display, not print)
                if(type === 'percentage' && percent !== '' && percent !== null && percent !== '-') {
                    var percentValue = parseFloat(percent);
                    if(percentValue >= 75) {
                        el.classList.add('text-success'); // Green for >= 75%
                    } else {
                        el.classList.add('text-danger'); // Red for < 75%
                    }
                }
            }
        });
    }
    
    if(type === 'percentage') {
        // Hide the percentage required rows when showing percentage view
        if(summaryLabel) {
            summaryLabel.closest('tr').style.display = 'none';
        }
        termSummaryLabels.forEach(function(label) {
            label.closest('tr').style.display = 'none';
        });
    } else {
        // Show the rows and set appropriate labels for other views
        if(summaryLabel) {
            summaryLabel.closest('tr').style.display = '';
            summaryLabel.textContent = 'Total number of items';
        }
        termSummaryLabels.forEach(function(label) {
            label.closest('tr').style.display = '';
            label.textContent = 'Total number of items';
        });
    }
}

function switchTerm(term, index) {
    currentTerm = term;
    var scoreType = document.getElementById('scoreType').value;
    
    // Hide combined tables and all term tables
    var combinedTable = document.getElementById('combined-table');
    var termTables = document.querySelectorAll('.term-table');
    var passfailTable = document.getElementById('passfail-table');
    var copasssummaryTable = document.getElementById('copasssummary-table');
    var passfailTermTables = document.querySelectorAll('.passfail-term-table');
    var summaryTermTables = document.querySelectorAll('.summary-term-table');
    
    if (combinedTable) combinedTable.style.display = 'none';
    termTables.forEach(function(tbl) { tbl.style.display = 'none'; });
    
    if (scoreType === 'passfail') {
        if (passfailTable) passfailTable.style.display = 'none';
        passfailTermTables.forEach(function(tbl) { tbl.style.display = 'none'; });
        
        // Show selected passfail term table
        var activePassfailTable = document.getElementById('passfail-term-' + term);
        if (activePassfailTable) activePassfailTable.style.display = 'block';
    } else if (scoreType === 'copasssummary') {
        if (copasssummaryTable) copasssummaryTable.style.display = 'none';
        summaryTermTables.forEach(function(tbl) { tbl.style.display = 'none'; });
        
        // Show selected summary term table
        var activeSummaryTable = document.getElementById('summary-term-' + term);
        if (activeSummaryTable) activeSummaryTable.style.display = 'block';
    } else {
        // Show selected regular term table
        var activeTable = document.getElementById('term-' + term);
        if (activeTable) activeTable.style.display = 'block';
    }
    
    // Update compact stepper appearance
    var allSteps = document.querySelectorAll('.compact-step');
    
    allSteps.forEach(function(step, i) {
        step.classList.remove('active', 'completed', 'upcoming');
        
        if (i === 0) {
            // All Terms button - keep it completed when individual term is selected
            step.classList.add('completed');
        } else {
            // Individual term buttons (index 1-4)
            var termIndex = i - 1; // Adjust for All Terms button
            
            if (termIndex < index) {
                // Completed terms
                step.classList.add('completed');
            } else if (termIndex === index) {
                // Active term (clicked)
                step.classList.add('active');
            } else {
                // Upcoming terms
                step.classList.add('upcoming');
            }
        }
    });
    
    // Update score display based on current type
    var type = document.getElementById('scoreType').value;
    document.querySelectorAll('.score-value').forEach(function(el) {
        var score = el.getAttribute('data-score');
        var percent = el.getAttribute('data-percentage');
        if(type === 'score') {
            el.textContent = score;
        } else {
            el.textContent = percent !== '' && percent !== null ? percent + '%' : '-';
        }
    });
}

function showAllTerms() {
    currentTerm = null;
    var scoreType = document.getElementById('scoreType').value;
    
    // Hide all term tables
    var termTables = document.querySelectorAll('.term-table');
    var passfailTermTables = document.querySelectorAll('.passfail-term-table');
    var summaryTermTables = document.querySelectorAll('.summary-term-table');
    
    termTables.forEach(function(tbl) { tbl.style.display = 'none'; });
    passfailTermTables.forEach(function(tbl) { tbl.style.display = 'none'; });
    summaryTermTables.forEach(function(tbl) { tbl.style.display = 'none'; });
    
    // Show appropriate combined table based on score type
    if (scoreType === 'passfail') {
        var passfailTable = document.getElementById('passfail-table');
        if (passfailTable) passfailTable.style.display = 'block';
    } else if (scoreType === 'copasssummary') {
        var copasssummaryTable = document.getElementById('copasssummary-table');
        if (copasssummaryTable) copasssummaryTable.style.display = 'block';
    } else {
        var combinedTable = document.getElementById('combined-table');
        if (combinedTable) combinedTable.style.display = 'block';
    }
    
    // Reset compact stepper to default state
    var allSteps = document.querySelectorAll('.compact-step');
    allSteps.forEach(function(step, i) {
        step.classList.remove('active', 'completed', 'upcoming');
        if (i === 0) {
            // All Terms button - active
            step.classList.add('active');
        } else {
            // Individual term buttons - upcoming
            step.classList.add('upcoming');
        }
    });
    
    toggleScoreType();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleScoreType();
    
    // Add smooth scrolling to table when switching
    document.querySelectorAll('.term-step').forEach(function(step) {
        step.addEventListener('click', function() {
            setTimeout(function() {
                const tableContainer = document.querySelector('.results-card:not([style*="display: none"])');
                if (tableContainer) {
                    tableContainer.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }
            }, 100);
        });
    });
});

function printTable() {
    printSpecificTable('combined');
}

function printSpecificTable(tableType) {
    const bannerUrl = window.bannerUrl || '/images/banner-header.png';
    
    // Get current academic period and subject info from global variables
    const academicPeriod = window.academicPeriod || 'N/A';
    const semester = window.semester || 'N/A';
    const currentDate = new Date().toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    // Get subject information from global variables
    const subjectInfo = window.subjectInfo || 'Course Outcome Results';
    const courseCode = window.courseCode || 'N/A';
    const subjectDescription = window.subjectDescription || 'N/A';
    const units = window.units || 'N/A';
    const courseSection = window.courseSection || 'N/A';
    
    let content = '';
    let reportTitle = '';
    
    switch(tableType) {
        case 'prelim':
            content = getPrintTableContent('prelim');
            reportTitle = 'Course Outcome Attainment Results - Prelim Term';
            break;
        case 'midterm':
            content = getPrintTableContent('midterm');
            reportTitle = 'Course Outcome Attainment Results - Midterm';
            break;
        case 'prefinal':
            content = getPrintTableContent('prefinal');
            reportTitle = 'Course Outcome Attainment Results - Prefinal Term';
            break;
        case 'final':
            content = getPrintTableContent('final');
            reportTitle = 'Course Outcome Attainment Results - Final Term';
            break;
        case 'combined':
            content = getPrintTableContent('combined');
            reportTitle = 'Course Outcome Attainment Results - All Terms Combined';
            break;
        case 'passfail':
            content = getPassFailContent();
            reportTitle = 'Course Outcome Pass/Fail Analysis Report';
            break;
        case 'copasssummary':
            content = getCourseOutcomeSummaryContent();
            reportTitle = 'Course Outcomes Summary Dashboard Report';
            break;
        case 'all':
            content = getAllTablesContent();
            reportTitle = 'Complete Course Outcome Attainment Report';
            break;
        default:
            content = getPrintTableContent('combined');
            reportTitle = 'Course Outcome Attainment Results';
    }
    
    const printWindow = window.open('', '', 'width=900,height=650');
    printWindow.document.write(`
        <html>
            <head>
                <title>${reportTitle}</title>
                <style>
                    @media print {
                        @page {
                            size: A4 portrait;
                            margin: 0.75in 0.5in;
                        }
                        
                        body {
                            font-size: 10px;
                        }
                        
                        table {
                            font-size: 9px;
                        }
                        
                        .banner {
                            max-height: 100px;
                        }
                        
                        .report-title {
                            font-size: 16px;
                        }
                        
                        .percentage-value {
                            color: #000000 !important;
                        }
                        
                        /* Remove color coding in print view */
                        .text-success, .text-danger {
                            color: #000000 !important;
                        }
                    }
                    
                    body {
                        font-family: 'Arial', sans-serif;
                        margin: 0;
                        padding: 20px;
                        color: #333;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                        line-height: 1.6;
                    }

                    .banner {
                        width: 100%;
                        max-height: 130px;
                        object-fit: contain;
                        margin-bottom: 15px;
                    }

                    .header-content {
                        margin-bottom: 20px;
                    }

                    .report-title {
                        font-size: 20px;
                        font-weight: bold;
                        text-align: center;
                        margin: 15px 0;
                        text-transform: uppercase;
                        letter-spacing: 1px;
                        color: #4a7c59;
                        border-bottom: 2px solid #4a7c59;
                        padding-bottom: 8px;
                    }

                    .header-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 25px;
                        background-color: #fff;
                        font-size: 11px;
                        border: 2px solid #4a7c59;
                    }

                    .header-table td {
                        padding: 8px 12px;
                        border: 1px solid #2d4a35;
                    }

                    .header-label {
                        font-weight: bold;
                        width: 120px;
                        background-color: #4a7c59;
                        color: #fff;
                    }

                    .header-value {
                        font-family: 'Arial', sans-serif;
                        font-weight: 500;
                    }

                    .print-table {
                        width: 100%;
                        border-collapse: collapse;
                        border: 2px solid #4a7c59;
                        background-color: #fff;
                        margin-top: 15px;
                        font-size: 10px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }

                    .print-table th, .print-table td {
                        border: 1px solid #2d4a35;
                        padding: 6px 4px;
                        text-align: center;
                        vertical-align: middle;
                    }

                    .print-table th {
                        background-color: #4a7c59;
                        color: #fff;
                        font-weight: bold;
                        text-transform: uppercase;
                        white-space: nowrap;
                        font-size: 9px;
                    }

                    .print-table th:first-child {
                        background-color: #2d4a35;
                        text-align: left;
                    }

                    /* Multi-level header styles for All Terms Combined */
                    .print-table .table-success th,
                    .print-table th.table-success {
                        background-color: #4a7c59 !important;
                        color: white !important;
                    }

                    /* TOTAL columns - darker green */
                    .print-table .bg-primary,
                    .print-table th.bg-primary,
                    .print-table td.bg-primary {
                        background-color: #2d4a35 !important;
                        color: white !important;
                        font-weight: bold !important;
                    }

                    /* First header row - Students and CO headers */
                    .print-table thead tr:first-child th {
                        background-color: #4a7c59 !important;
                        color: white !important;
                        font-size: 10px !important;
                        padding: 8px 4px !important;
                        text-align: center !important;
                        font-weight: bold !important;
                    }

                    /* Second header row - term columns */
                    .print-table thead tr:nth-child(2) th {
                        background-color: #4a7c59 !important;
                        color: white !important;
                        font-size: 8px !important;
                        padding: 6px 2px !important;
                    }

                    /* Total columns in second row */
                    .print-table thead tr:nth-child(2) th.bg-primary {
                        background-color: #2d4a35 !important;
                    }

                    /* Data cells styling */
                    .print-table tbody td {
                        background-color: white !important;
                        font-size: 8px !important;
                        padding: 4px 2px !important;
                    }

                    /* Students column */
                    .print-table tbody td:first-child {
                        text-align: left !important;
                        background-color: #f8f9fa !important;
                        font-weight: normal !important;
                        padding-left: 6px !important;
                    }

                    /* Summary row styling */
                    .print-table tbody tr:first-child td {
                        background-color: #e8f5e8 !important;
                        font-weight: bold !important;
                    }

                    /* Score values */
                    .print-table .score-value {
                        font-weight: bold !important;
                        color: #000 !important;
                    }

                    /* Light background cells */
                    .print-table .bg-light {
                        background-color: #f8f9fa !important;
                    }

                    .print-table tr:nth-child(even) {
                        background-color: #f0f7f4;
                    }

                    .print-table td:first-child {
                        text-align: left;
                        font-weight: 500;
                        background-color: #f8f9fa;
                    }

                    .score-value {
                        font-weight: bold;
                        color: #1a5f38;
                    }

                    .percentage-value {
                        color: #000000;
                        font-weight: 500;
                    }

                    .average-cell {
                        background-color: #e8f5e8 !important;
                        font-weight: bold;
                        color: #1a5f38;
                    }

                    .term-section {
                        margin-bottom: 30px;
                        page-break-inside: avoid;
                    }

                    .term-title {
                        font-size: 16px;
                        font-weight: bold;
                        color: #1a5f38;
                        margin: 20px 0 10px 0;
                        padding: 8px 12px;
                        background-color: #f0f7f4;
                        border-left: 4px solid #1a5f38;
                    }

                    .footer {
                        margin-top: 20px;
                        padding-top: 15px;
                        border-top: 1px solid #dee2e6;
                        font-size: 11px;
                        color: #666;
                        text-align: center;
                    }

                    .page-break {
                        page-break-before: always;
                    }
                </style>
            </head>
            <body>
                <img src="${bannerUrl}" alt="Banner Header" class="banner">
                
                <div class="header-content">
                    <div class="report-title">${reportTitle}</div>
                    
                    <table class="header-table">
                        <tr>
                            <td class="header-label">Course Code:</td>
                            <td class="header-value">${courseCode}</td>
                            <td class="header-label">Units:</td>
                            <td class="header-value">${units}</td>
                        </tr>
                        <tr>
                            <td class="header-label">Description:</td>
                            <td class="header-value">${subjectDescription}</td>
                            <td class="header-label">Semester:</td>
                            <td class="header-value">${semester}</td>
                        </tr>
                        <tr>
                            <td class="header-label">Course/Section:</td>
                            <td class="header-value">${courseSection}</td>
                            <td class="header-label">School Year:</td>
                            <td class="header-value">${academicPeriod}</td>
                        </tr>
                    </table>
                </div>

                ${content}

                <div class="footer">
                    This is a computer-generated document. No signature is required.
                    <br>
                    Printed via ACADEX - Academic Grade System on ${currentDate}
                </div>
            </body>
        </html>
    `);
    printWindow.document.close();
    
    // Wait for resources to load then print
    setTimeout(() => {
        printWindow.print();
    }, 500);
}

function getPrintTableContent(termType) {
    let tableSelector = '';
    let termTitle = '';
    
    switch(termType) {
        case 'prelim':
            tableSelector = '#term-prelim table';
            termTitle = 'Prelim Term Results';
            break;
        case 'midterm':
            tableSelector = '#term-midterm table';
            termTitle = 'Midterm Results';
            break;
        case 'prefinal':
            tableSelector = '#term-prefinal table';
            termTitle = 'Prefinal Term Results';
            break;
        case 'final':
            tableSelector = '#term-final table';
            termTitle = 'Final Term Results';
            break;
        case 'combined':
            tableSelector = '#combined-table table';
            termTitle = 'All Terms Combined';
            break;
    }
    
    const table = document.querySelector(tableSelector);
    if (!table) {
        return '<p>No data available for the selected term.</p>';
    }
    
    let tableHTML = `<div class="term-section">`;
    if (termType !== 'combined') {
        tableHTML += `<h3 class="term-title">${termTitle}</h3>`;
    }
    
    tableHTML += `<table class="print-table">`;
    
    // Copy table content with proper attributes
    const rows = table.querySelectorAll('tr');
    const currentScoreType = document.getElementById('scoreType').value;
    
    rows.forEach((row, index) => {
        const isHeader = row.closest('thead') !== null;
        const tag = isHeader ? 'th' : 'td';
        
        // Skip "Total number of items" row if in percentage view
        if (currentScoreType === 'percentage' && !isHeader) {
            const firstCell = row.querySelector('td');
            if (firstCell && firstCell.textContent.includes('Total number of items')) {
                return;
            }
        }
        
        tableHTML += '<tr>';
        const cells = row.querySelectorAll(isHeader ? 'th' : 'td');
        cells.forEach(cell => {
            let cellContent = cell.textContent.trim();
            let cellClass = '';
            let cellAttrs = '';
            
            // Preserve colspan and rowspan attributes
            if (cell.hasAttribute('colspan')) {
                cellAttrs += ` colspan="${cell.getAttribute('colspan')}"`;
            }
            if (cell.hasAttribute('rowspan')) {
                cellAttrs += ` rowspan="${cell.getAttribute('rowspan')}"`;
            }
            
            // Preserve important CSS classes
            if (cell.classList.contains('bg-primary') || cell.classList.contains('text-white')) {
                cellClass += ' bg-primary text-white';
            }
            if (cell.classList.contains('table-success')) {
                cellClass += ' table-success';
            }
            if (cell.classList.contains('align-middle')) {
                cellClass += ' align-middle';
            }
            if (cell.classList.contains('text-center')) {
                cellClass += ' text-center';
            }
            if (cell.classList.contains('fw-bold')) {
                cellClass += ' fw-bold';
            }
            if (cell.classList.contains('bg-light')) {
                cellClass += ' bg-light';
            }
            
            // Add special styling for different cell types
            if (cell.classList.contains('score-value') || cellContent.match(/^\d+$/)) {
                cellClass += ' score-value';
            } else if (cellContent.includes('%')) {
                cellClass += ' percentage-value';
            } else if (cell.textContent.includes('Average') || cell.classList.contains('average-cell')) {
                cellClass += ' average-cell';
            }
            
            // Check for inline styles (like the bg-primary style)
            if (cell.style && cell.style.cssText) {
                cellAttrs += ` style="${cell.style.cssText}"`;
            }
            
            tableHTML += `<${tag}${cellAttrs} class="${cellClass.trim()}">${cellContent}</${tag}>`;
        });
        tableHTML += '</tr>';
    });
    
    tableHTML += '</table></div>';
    return tableHTML;
}

function getAllTablesContent() {
    let content = '';
    const terms = ['prelim', 'midterm', 'prefinal', 'final'];
    
    terms.forEach((term, index) => {
        if (index > 0) {
            content += '<div class="page-break"></div>';
        }
        content += getPrintTableContent(term);
    });
    
    // Add combined table
    content += '<div class="page-break"></div>';
    content += getPrintTableContent('combined');
    
    // Add Pass/Fail Analysis
    content += '<div class="page-break"></div>';
    content += getPassFailContent();
    
    // Add Course Outcomes Summary
    content += '<div class="page-break"></div>';
    content += getCourseOutcomeSummaryContent();
    
    return content;
}

function getPassFailContent() {
    const passFailTable = document.querySelector('#passfail-table table');
    if (!passFailTable) {
        return '<p>No Pass/Fail analysis data available.</p>';
    }
    
    let content = '<div class="term-section">';
    content += '<h3 class="term-title">Pass/Fail Analysis Summary</h3>';
    content += '<table class="print-table">';
    
    // Copy the Pass/Fail table content
    const rows = passFailTable.querySelectorAll('tr');
    rows.forEach((row) => {
        const isHeader = row.closest('thead') !== null;
        const tag = isHeader ? 'th' : 'td';
        
        content += '<tr>';
        const cells = row.querySelectorAll(isHeader ? 'th' : 'td');
        cells.forEach(cell => {
            let cellContent = cell.textContent.trim();
            let cellClass = '';
            let cellAttrs = '';
            
            // Preserve attributes
            if (cell.hasAttribute('colspan')) {
                cellAttrs += ` colspan="${cell.getAttribute('colspan')}"`;
            }
            if (cell.hasAttribute('rowspan')) {
                cellAttrs += ` rowspan="${cell.getAttribute('rowspan')}"`;
            }
            
            // Preserve important CSS classes
            if (cell.classList.contains('table-success')) {
                cellClass += ' table-success';
            }
            if (cell.classList.contains('text-center')) {
                cellClass += ' text-center';
            }
            if (cell.classList.contains('fw-bold')) {
                cellClass += ' fw-bold';
            }
            if (cell.classList.contains('bg-light')) {
                cellClass += ' bg-light';
            }
            
            content += `<${tag}${cellAttrs} class="${cellClass.trim()}">${cellContent}</${tag}>`;
        });
        content += '</tr>';
    });
    
    content += '</table></div>';
    return content;
}

function getCourseOutcomeSummaryContent() {
    const summaryTable = document.querySelector('#copasssummary-table table');
    if (!summaryTable) {
        return '<p>No Course Outcomes Summary data available.</p>';
    }
    
    let content = '<div class="term-section">';
    content += '<h3 class="term-title">Course Outcomes Summary Dashboard</h3>';
    content += '<table class="print-table">';
    
    // Copy the Course Outcomes Summary table content
    const rows = summaryTable.querySelectorAll('tr');
    rows.forEach((row) => {
        const isHeader = row.closest('thead') !== null;
        const tag = isHeader ? 'th' : 'td';
        
        content += '<tr>';
        const cells = row.querySelectorAll(isHeader ? 'th' : 'td');
        cells.forEach(cell => {
            let cellContent = cell.textContent.trim();
            let cellClass = '';
            let cellAttrs = '';
            
            // Preserve attributes
            if (cell.hasAttribute('colspan')) {
                cellAttrs += ` colspan="${cell.getAttribute('colspan')}"`;
            }
            if (cell.hasAttribute('rowspan')) {
                cellAttrs += ` rowspan="${cell.getAttribute('rowspan')}"`;
            }
            
            // Preserve important CSS classes
            if (cell.classList.contains('table-success')) {
                cellClass += ' table-success';
            }
            if (cell.classList.contains('text-center')) {
                cellClass += ' text-center';
            }
            if (cell.classList.contains('fw-bold')) {
                cellClass += ' fw-bold';
            }
            if (cell.classList.contains('bg-light')) {
                cellClass += ' bg-light';
            }
            if (cell.classList.contains('average-cell')) {
                cellClass += ' average-cell';
            }
            
            // Handle percentage values
            if (cellContent.includes('%')) {
                cellClass += ' percentage-value';
            }
            
            content += `<${tag}${cellAttrs} class="${cellClass.trim()}">${cellContent}</${tag}>`;
        });
        content += '</tr>';
    });
    
    content += '</table></div>';
    return content;
}

// Warning system functions
function dismissWarning() {
    const warningAlert = document.querySelector('.alert-warning');
    if (warningAlert) {
        warningAlert.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
        warningAlert.style.opacity = '0';
        warningAlert.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            warningAlert.style.display = 'none';
        }, 300);
    }
}

function refreshData() {
    // Show loading state
    const refreshButton = document.querySelector('button[onclick="refreshData()"]');
    if (refreshButton) {
        const originalHTML = refreshButton.innerHTML;
        refreshButton.innerHTML = '<i class="bi bi-arrow-clockwise me-2 spin"></i>Refreshing...';
        refreshButton.disabled = true;
        
        // Add spinning animation
        const style = document.createElement('style');
        style.textContent = `
            .spin {
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
        
        // Reload the page after a short delay
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
}

// Function to close the print modal
function closePrintModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('printOptionsModal'));
    if (modal) {
        modal.hide();
    }
}
