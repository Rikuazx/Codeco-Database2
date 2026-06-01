/* Statistic for teachers */
<h2>Teacher Management</h2>

<div>
    Total Teachers:
    <span id="total_teachers">0</span>
</div>

<hr>

// Teacher KPI
<h2>Teacher KPI</h2>

<button onclick="calculateKpi()">
    Calculate KPI
</button>

<div id="kpi_result"></div>

<h2>Teacher KPI</h2>

<button onclick="loadKpi()">
    Load KPI
</button>

<ul id="kpi_list"></ul>

// Search and list teachers
<input
    id="search_teacher"
    placeholder="Search teacher"
    onkeyup="loadTeachers()">

    <ul id="teachers"></ul>

//Availability
<h2>Teacher Availability</h2>

<button onclick="loadAvailability()">
    Load Availability
</button>

<ul id="availability_list"></ul>

//KPI Calculation
<hr>

<h2>Teacher KPI</h2>

<div>
    Category A:
    <span id="kpi_a">0</span>

    |

    Category B:
    <span id="kpi_b">0</span>

    |

    Category C:
    <span id="kpi_c">0</span>
</div>

<br>

<button onclick="loadKpis()">
    Load KPI
</button>

<ul id="kpi_list"></ul>

// Edit teacher form
<input id="teacher_id" hidden>

<input
    id="teacher_name"
    placeholder="Name">

<input
    id="teacher_email"
    placeholder="Email">

<input
    id="teacher_specialization"
    placeholder="Specialization">

<input
    id="teacher_priority_score"
    type="number"
    placeholder="Priority Score">

<button onclick="saveTeacher()">
    Save Teacher
</button>

//Javascript
<script>
    async function loadStats()
{
    const res =
        await fetch('/api/teachers/stats');

    const stats =
        await res.json();

    document.getElementById(
        'total_teachers'
    ).innerText = stats.total;
}

let teachersData = [];

async function loadTeachers()
{
    const res =
        await fetch('/api/teachers');

    let data =
        await res.json();

    const search =
        document.getElementById(
            'search_teacher'
        ).value.toLowerCase();

    data = data.filter(t =>
        t.user.name
            .toLowerCase()
            .includes(search)
    );

    teachersData = data;

    let html = '';

    data.forEach(t => {

        html += `
        <li>

            ${t.user.name}
            |
            ${t.user.email}
            |
            ${t.specialization ?? '-'}

            |
            Priority:
            ${t.priority_score}

            <button
                onclick="editTeacher(${t.id})">
                Edit
            </button>

            <button
                onclick="deleteTeacher(${t.id})">
                Delete
            </button>

        </li>
        `;
    });

    document.getElementById(
        'teachers'
    ).innerHTML = html;
}

let availabilityData = [];

async function loadAvailability()
{
    const res =
        await fetch('/api/teacher-availability');

    const data =
        await res.json();

    availabilityData = data;

    let html = '';

    data.forEach(a => {

        html += `
        <li>

            ${a.teacher.user.name}

            |

            ${a.type}

            |

            ${a.period_start}

            →

            ${a.period_end}

            <button
                onclick="deleteAvailability(${a.id})">

                Delete

            </button>

        </li>
        `;
    });

    document.getElementById(
        'availability_list'
    ).innerHTML = html;
}

async function calculateKpi()
{
    const teacherId =
        document.getElementById(
            'teacher_id'
        ).value;

    if (!teacherId)
    {
        alert('Select a teacher first');
        return;
    }

    const month =
        new Date().getMonth() + 1;

    const year =
        new Date().getFullYear();

    const res =
        await fetch(
            `/api/teachers/${teacherId}/kpi/${month}/${year}`,
            {
                method: 'POST'
            }
        );

    const data =
        await res.json();

    document.getElementById(
        'kpi_result'
    ).innerHTML =
        `
        Score:
        ${data.total_score}
        `;
}

async function loadKpi()
{
    const res =
        await fetch('/api/teacher-kpi');

    const data =
        await res.json();

    let html = '';

    data.forEach(k => {

        html += `
        <li>
            ${k.teacher.user.name}
            |
            Score:
            ${k.total_score}
            |
            Grade:
            ${k.category}
        </li>
        `;
    });

    document.getElementById(
        'kpi_list'
    ).innerHTML = html;
}

async function deleteAvailability(id)
{
    if (!confirm('Delete availability?'))
    {
        return;
    }

    const res =
        await fetch(
            `/api/teacher-availability/${id}`,
            {
                method: 'DELETE'
            }
        );

    const result =
        await res.json();

    alert(result.message);

    loadAvailability();
}

async function editTeacher(id)
{
    const teacher =
        teachersData.find(t => t.id === id);

    document.getElementById(
        'teacher_id'
    ).value = teacher.id;

    document.getElementById(
        'teacher_name'
    ).value = teacher.user.name;

    document.getElementById(
        'teacher_email'
    ).value = teacher.user.email;

    document.getElementById(
        'teacher_specialization'
    ).value = teacher.specialization ?? '';

    document.getElementById(
        'teacher_priority_score'
    ).value = teacher.priority_score;
}

async function saveTeacher()
{
    const id =
        document.getElementById(
            'teacher_id'
        ).value;

    const payload = {
        name:
            document.getElementById(
                'teacher_name'
            ).value,

        email:
            document.getElementById(
                'teacher_email'
            ).value,

        specialization:
            document.getElementById(
                'teacher_specialization'
            ).value,

        priority_score:
            document.getElementById(
                'teacher_priority_score'
            ).value
    };

    const res =
        await fetch(
            `/api/teachers/${id}`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type':
                        'application/json'
                },
                body: JSON.stringify(payload)
            }
        );

    const result =
        await res.json();

    alert(result.message);

    loadTeachers();
}

async function deleteTeacher(id)
{
    if (!confirm('Delete teacher?'))
    {
        return;
    }

    const res =
        await fetch(
            `/api/teachers/${id}`,
            {
                method: 'DELETE'
            }
        );

    const result =
        await res.json();

    alert(result.message);

    loadTeachers();
    loadStats();
}
async function loadKpis()
{
    const res =
        await fetch('/api/teacher-kpi');

    const data =
        await res.json();

    let countA = 0;
    let countB = 0;
    let countC = 0;

    let html = '';

    data.forEach(kpi => {

        if (kpi.category === 'A')
            countA++;

        if (kpi.category === 'B')
            countB++;

        if (kpi.category === 'C')
            countC++;

        html += `
        <li>

            ${kpi.teacher.user.name}

            |

            Month:
            ${kpi.month}

            /

            ${kpi.year}

            |

            Score:
            ${kpi.total_score}

            |

            Category:
            ${kpi.category}

        </li>
        `;
    });

    document.getElementById(
        'kpi_a'
    ).innerText = countA;

    document.getElementById(
        'kpi_b'
    ).innerText = countB;

    document.getElementById(
        'kpi_c'
    ).innerText = countC;

    document.getElementById(
        'kpi_list'
    ).innerHTML = html;
}


loadStats();
loadTeachers();
loadAvailability();
loadKpis();
</script>

