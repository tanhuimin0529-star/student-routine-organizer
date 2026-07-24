// =====================================================================
// dashboard.js
// Fitness Dashboard — Chart.js setup, AJAX saves, progress rings,
// heatmap rendering, water/sleep controls
// =====================================================================

// ---------------------------------------------------------------
// Utility: Create SVG Circular Progress Ring
// ---------------------------------------------------------------
function initProgressRing(containerId, percentage, color) {
    var container = document.getElementById(containerId);
    if (!container) return;

    var svg = container.querySelector('.progress-ring');
    var fill = svg ? svg.querySelector('.progress-ring-fill') : null;
    if (!fill) return;

    var radius = parseFloat(fill.getAttribute('r'));
    var circumference = 2 * Math.PI * radius;

    fill.style.strokeDasharray = circumference;
    fill.style.strokeDashoffset = circumference;

    // Animate after short delay
    setTimeout(function () {
        var offset = circumference - (percentage / 100) * circumference;
        fill.style.strokeDashoffset = offset;
        fill.style.stroke = color || getProgressColor(percentage);
    }, 300);
}

function getProgressColor(percentage) {
    if (percentage >= 100) return '#34D399';       // green
    if (percentage >= 70) return '#FB923C';        // orange
    if (percentage >= 40) return '#FBBF24';        // yellow
    return '#F87171';                                // red
}

function getCalorieColor(percentage) {
    if (percentage >= 100) return '#34D399';        // green — goal achieved
    if (percentage >= 75) return '#FB923C';        // orange — approaching
    return '#F87171';                                // red — low progress
}

// ---------------------------------------------------------------
// Chart.js: Weekly Progress (Bar Chart)
// ---------------------------------------------------------------
function initWeeklyChart(canvasId, labels, data) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;

    var ctx = canvas.getContext('2d');

    var gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(167, 139, 250, 0.8)');
    gradient.addColorStop(1, 'rgba(96, 165, 250, 0.4)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Calories Burned',
                data: data,
                backgroundColor: gradient,
                borderColor: 'rgba(167, 139, 250, 0.9)',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1500, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(31, 41, 55, 0.9)',
                    titleFont: { family: 'Poppins', size: 12 },
                    bodyFont: { family: 'Poppins', size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (ctx) { return ctx.parsed.y + ' kcal'; }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(229, 231, 235, 0.3)', drawBorder: false },
                    ticks: { font: { family: 'Poppins', size: 11 }, color: '#9CA3AF' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 11 }, color: '#9CA3AF' }
                }
            }
        }
    });
}

// ---------------------------------------------------------------
// Chart.js: Monthly Workout Trend (Line Chart)
// ---------------------------------------------------------------
function initMonthlyTrendChart(canvasId, labels, data) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    var gradient = ctx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, 'rgba(96, 165, 250, 0.3)');
    gradient.addColorStop(1, 'rgba(96, 165, 250, 0.01)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Calories Burned',
                data: data,
                borderColor: '#60A5FA',
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#60A5FA',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 2.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1800, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(31, 41, 55, 0.9)',
                    titleFont: { family: 'Poppins', size: 12 },
                    bodyFont: { family: 'Poppins', size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (ctx) { return ctx.parsed.y + ' kcal'; }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(229, 231, 235, 0.3)', drawBorder: false },
                    ticks: { font: { family: 'Poppins', size: 11 }, color: '#9CA3AF' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 10 }, color: '#9CA3AF', maxRotation: 45 }
                }
            }
        }
    });
}

// ---------------------------------------------------------------
// Chart.js: Exercise Distribution (Pie Chart)
// ---------------------------------------------------------------
function initDistributionChart(canvasId, labels, data) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    var colors = [
        'rgba(167, 139, 250, 0.85)',
        'rgba(96, 165, 250, 0.85)',
        'rgba(34, 211, 238, 0.85)',
        'rgba(52, 211, 153, 0.85)',
        'rgba(251, 146, 60, 0.85)',
        'rgba(248, 113, 113, 0.85)',
        'rgba(251, 191, 36, 0.85)',
        'rgba(196, 181, 253, 0.85)'
    ];

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, data.length),
                borderColor: 'rgba(255,255,255,0.8)',
                borderWidth: 2,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1500, easing: 'easeOutQuart', animateRotate: true },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { family: 'Poppins', size: 11 },
                        color: '#6B7280',
                        padding: 12,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(31, 41, 55, 0.9)',
                    titleFont: { family: 'Poppins', size: 12 },
                    bodyFont: { family: 'Poppins', size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (ctx) {
                            var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                            var pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                            return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                        }
                    }
                }
            },
            cutout: '55%'
        }
    });
}

// ---------------------------------------------------------------
// Chart.js: Duration Trend (Line Chart)
// ---------------------------------------------------------------
function initDurationTrendChart(canvasId, labels, data) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;

    var ctx = canvas.getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Duration (min)',
                data: data,
                borderColor: '#22D3EE',
                backgroundColor: 'rgba(34, 211, 238, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#22D3EE',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                borderWidth: 2.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1800, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(31, 41, 55, 0.9)',
                    titleFont: { family: 'Poppins', size: 12 },
                    bodyFont: { family: 'Poppins', size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (ctx) { return ctx.parsed.y + ' min'; }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(229, 231, 235, 0.3)', drawBorder: false },
                    ticks: { font: { family: 'Poppins', size: 11 }, color: '#9CA3AF' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 10 }, color: '#9CA3AF', maxRotation: 45 }
                }
            }
        }
    });
}

// ---------------------------------------------------------------
// Chart.js: Calories Trend (Area Chart)
// ---------------------------------------------------------------
function initCaloriesTrendChart(canvasId, labels, data) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    var gradient = ctx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, 'rgba(52, 211, 153, 0.35)');
    gradient.addColorStop(1, 'rgba(52, 211, 153, 0.02)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Calories',
                data: data,
                borderColor: '#34D399',
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#34D399',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                borderWidth: 2.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1800, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(31, 41, 55, 0.9)',
                    titleFont: { family: 'Poppins', size: 12 },
                    bodyFont: { family: 'Poppins', size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (ctx) { return ctx.parsed.y + ' kcal'; }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(229, 231, 235, 0.3)', drawBorder: false },
                    ticks: { font: { family: 'Poppins', size: 11 }, color: '#9CA3AF' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 10 }, color: '#9CA3AF', maxRotation: 45 }
                }
            }
        }
    });
}

// ---------------------------------------------------------------
// Chart.js: Goal Achievement (Doughnut)
// ---------------------------------------------------------------
function initGoalChart(canvasId, achieved, remaining) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    var pct = remaining <= 0 ? 100 : Math.round((achieved / (achieved + remaining)) * 100);
    var color = getCalorieColor(pct);

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Burned', 'Remaining'],
            datasets: [{
                data: [achieved, Math.max(0, remaining)],
                backgroundColor: [color, 'rgba(229, 231, 235, 0.4)'],
                borderColor: 'rgba(255,255,255,0.8)',
                borderWidth: 2,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1500, easing: 'easeOutQuart', animateRotate: true },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(31, 41, 55, 0.9)',
                    titleFont: { family: 'Poppins', size: 12 },
                    bodyFont: { family: 'Poppins', size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (ctx) { return ctx.label + ': ' + ctx.parsed + ' kcal'; }
                    }
                }
            },
            cutout: '70%'
        }
    });
}

// ---------------------------------------------------------------
// AJAX: Save Profile Data
// ---------------------------------------------------------------
function saveProfileField(field, value, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'save_profile.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showToast(response.message || 'Saved!', 'success');
                        if (callback) callback(response);
                    } else {
                        showToast(response.message || 'Error saving', 'error');
                    }
                } catch (e) {
                    showToast('Error saving data', 'error');
                }
            } else {
                showToast('Connection error', 'error');
            }
        }
    };
    xhr.send('field=' + encodeURIComponent(field) + '&value=' + encodeURIComponent(value));
}

// ---------------------------------------------------------------
// Calorie Goal Save
// ---------------------------------------------------------------
function initCalorieGoalSave() {
    var saveBtn = document.getElementById('save-calorie-goal');
    if (!saveBtn) return;

    saveBtn.addEventListener('click', function () {
        var input = document.getElementById('calorie-goal-input');
        var value = parseInt(input.value);
        if (isNaN(value) || value < 1) {
            showToast('Enter a valid calorie goal', 'warning');
            return;
        }
        saveProfileField('daily_calorie_goal', value, function () {
            setTimeout(function () { location.reload(); }, 800);
        });
    });
}

// ---------------------------------------------------------------
// Steps Save
// ---------------------------------------------------------------
function initStepsSave() {
    var saveBtn = document.getElementById('save-steps');
    if (!saveBtn) return;

    saveBtn.addEventListener('click', function () {
        var stepsInput = document.getElementById('steps-input');
        var goalInput = document.getElementById('steps-goal-input');
        var steps = parseInt(stepsInput.value);
        var goal = parseInt(goalInput.value);

        if (isNaN(steps) || steps < 0) {
            showToast('Enter valid steps', 'warning');
            return;
        }
        if (isNaN(goal) || goal < 1) {
            showToast('Enter a valid step goal', 'warning');
            return;
        }

        // Save both
        saveProfileField('current_steps', steps, function () {
            saveProfileField('daily_step_goal', goal, function () {
                setTimeout(function () { location.reload(); }, 800);
            });
        });
    });
}

// ---------------------------------------------------------------
// Weight & Height Save
// ---------------------------------------------------------------
function initBodySave() {
    var saveBtn = document.getElementById('save-body');
    if (!saveBtn) return;

    saveBtn.addEventListener('click', function () {
        var weight = parseFloat(document.getElementById('weight-input').value);
        var height = parseFloat(document.getElementById('height-input').value);

        if (isNaN(weight) || weight < 1) {
            showToast('Enter a valid weight', 'warning');
            return;
        }
        if (isNaN(height) || height < 1) {
            showToast('Enter a valid height', 'warning');
            return;
        }

        saveProfileField('weight_kg', weight, function () {
            saveProfileField('height_cm', height, function () {
                setTimeout(function () { location.reload(); }, 800);
            });
        });
    });
}

// ---------------------------------------------------------------
// Water Intake Controls
// ---------------------------------------------------------------
function initWaterControls() {
    var addBtn = document.getElementById('water-add');
    var subBtn = document.getElementById('water-sub');
    if (!addBtn || !subBtn) return;

    addBtn.addEventListener('click', function () {
        var current = parseInt(document.getElementById('water-current').dataset.value) || 0;
        var newVal = current + 250;
        saveProfileField('water_intake_ml', newVal, function () {
            document.getElementById('water-current').dataset.value = newVal;
            document.getElementById('water-current').textContent = newVal;
            updateWaterGlasses(newVal);
        });
    });

    subBtn.addEventListener('click', function () {
        var current = parseInt(document.getElementById('water-current').dataset.value) || 0;
        var newVal = Math.max(0, current - 250);
        saveProfileField('water_intake_ml', newVal, function () {
            document.getElementById('water-current').dataset.value = newVal;
            document.getElementById('water-current').textContent = newVal;
            updateWaterGlasses(newVal);
        });
    });
}

function updateWaterGlasses(ml) {
    var glasses = Math.floor(ml / 250);
    var allGlasses = document.querySelectorAll('.water-glass');
    allGlasses.forEach(function (g, i) {
        if (i < glasses) {
            g.classList.add('filled');
        } else {
            g.classList.remove('filled');
        }
    });
}

// ---------------------------------------------------------------
// Sleep Save
// ---------------------------------------------------------------
function initSleepSave() {
    var saveBtn = document.getElementById('save-sleep');
    if (!saveBtn) return;

    saveBtn.addEventListener('click', function () {
        var hours = parseFloat(document.getElementById('sleep-input').value);
        if (isNaN(hours) || hours < 0 || hours > 24) {
            showToast('Enter valid sleep hours (0-24)', 'warning');
            return;
        }
        saveProfileField('sleep_hours', hours, function () {
            updateSleepQuality(hours);
        });
    });
}

function updateSleepQuality(hours) {
    var badge = document.getElementById('sleep-quality-badge');
    if (!badge) return;
    if (hours >= 7) {
        badge.className = 'quality-badge quality-good';
        badge.textContent = '😴 Great Sleep!';
    } else if (hours >= 5) {
        badge.className = 'quality-badge quality-ok';
        badge.textContent = '😐 Could Be Better';
    } else {
        badge.className = 'quality-badge quality-poor';
        badge.textContent = '😟 Need More Sleep';
    }
}

// ---------------------------------------------------------------
// Heatmap Rendering
// ---------------------------------------------------------------
function initHeatmap(containerId, data) {
    var container = document.getElementById(containerId);
    if (!container) return;

    // data is an object: { "YYYY-MM-DD": count, ... }
    // Build 90 days of cells
    var grid = document.createElement('div');
    grid.className = 'heatmap-grid';

    var today = new Date();
    // Start from 90 days ago, aligned to start of week (Sunday)
    var startDate = new Date(today);
    startDate.setDate(startDate.getDate() - 89);
    // Align to Sunday
    var dayOfWeek = startDate.getDay();
    startDate.setDate(startDate.getDate() - dayOfWeek);

    var endDate = new Date(today);

    var current = new Date(startDate);
    while (current <= endDate) {
        var dateStr = current.toISOString().split('T')[0];
        var count = data[dateStr] || 0;
        var level = 0;
        if (count === 1) level = 1;
        else if (count === 2) level = 2;
        else if (count === 3) level = 3;
        else if (count >= 4) level = 4;

        var cell = document.createElement('div');
        cell.className = 'heatmap-cell level-' + level;
        cell.title = dateStr + ': ' + count + ' workout' + (count !== 1 ? 's' : '');
        grid.appendChild(cell);

        current.setDate(current.getDate() + 1);
    }

    container.appendChild(grid);

    // Legend
    var legend = document.createElement('div');
    legend.className = 'heatmap-legend';
    legend.innerHTML = '<span>Less</span>';
    for (var l = 0; l <= 4; l++) {
        legend.innerHTML += '<div class="heatmap-cell level-' + l + '"></div>';
    }
    legend.innerHTML += '<span>More</span>';
    container.appendChild(legend);
}

// ---------------------------------------------------------------
// Goal Achievement Check + Confetti
// ---------------------------------------------------------------
function checkGoalAchievement(caloriesBurned, calorieGoal) {
    if (calorieGoal > 0 && caloriesBurned >= calorieGoal) {
        var goalDiv = document.getElementById('goal-celebration');
        if (goalDiv && !goalDiv.dataset.shown) {
            goalDiv.dataset.shown = 'true';
            goalDiv.style.display = 'block';
            // Delay confetti slightly for visual impact
            setTimeout(function () {
                launchConfetti(4000);
            }, 500);
        }
    }
}

// ---------------------------------------------------------------
// Initialize Dashboard
// ---------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    initCalorieGoalSave();
    initStepsSave();
    initBodySave();
    initWaterControls();
    initSleepSave();
});
