<div class="page-wrapper">

    <div class="card">

        <!-- HEADER -->
        <div class="card-header">
            <h2>Raw Sheet</h2>
        </div>

        <!-- BODY -->
        <div class="card-body">

            <!-- PERSONAL DETAILS -->
            <div class="section">
                <h3>Personal Details</h3>
                <p class="subtitle">Please complete the employee data</p>
                <input type="text" placeholder="Search Employee" class="full-input">
            </div>

            <!-- GROSS SALARY -->
            <div class="section">
                <h3>Gross Salary</h3>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-4">
                    <input type="text" placeholder="Monthly">
                    <input type="text" placeholder="Semi-Monthly">
                    <input type="text" placeholder="Daily">
                    <input type="text" placeholder="Hourly">
                </div>
            </div>

            <!-- DE MINIMIS -->
            <div class="section">
                <h3>De Minimis Benefit</h3>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-3">
                    <input type="text" placeholder="Rice Subsidy">
                    <input type="text" placeholder="Uniform Clothing Allowance">
                    <input type="text" placeholder="Laundry Allowance">
                </div>
            </div>

            <!-- LOANS & ADVANCES -->
            <div class="grid-2">
                <div class="section">
                    <h3>SSS Loan</h3>
                    <p class="subtitle">Please complete the employee data</p>
                    <div class="grid-2">
                        <input type="text" placeholder="Salary">
                        <input type="text" placeholder="Deductibility">
                    </div>
                </div>

                <div class="section">
                    <h3>Advances to Employees</h3>
                    <p class="subtitle">Please complete the employee data</p>
                    <div class="grid-2">
                        <input type="text" placeholder="Salary">
                        <input type="text" placeholder="Deductibility">
                    </div>
                </div>
            </div>

            <!-- UNDERTIME & ABSENT -->
            <div class="grid-2">
                <div class="section">
                    <h3>Undertime</h3>
                    <p class="subtitle">Please complete the employee data</p>
                    <div class="grid-2">
                        <input type="text" placeholder="Hours">
                        <input type="text" placeholder="PHP Equivalent">
                    </div>
                </div>

                <div class="section">
                    <h3>Absent</h3>
                    <p class="subtitle">Please complete the employee data</p>
                    <div class="grid-2">
                        <input type="text" placeholder="Day(s)">
                        <input type="text" placeholder="PHP Equivalent">
                    </div>
                </div>
            </div>

            <!-- WORKDAYS OT -->
            <div class="section">
                <h3>Workdays Overtime</h3>
            </div>

            <div class="grid-2">
                <div class="section">
                    <h4>Overtime</h4>
                    <p class="subtitle">Please complete the employee data</p>
                    <div class="grid-2">
                        <input type="text" placeholder="Hours">
                        <input type="text" placeholder="PHP Equivalent">
                    </div>
                </div>

                <div class="section">
                    <h4>Night Differential 10PM - 6AM</h4>
                    <p class="subtitle">Please complete the employee data</p>
                    <div class="grid-2">
                        <input type="text" placeholder="Day(s)">
                        <input type="text" placeholder="PHP Equivalent">
                    </div>
                </div>
            </div>

            <!-- SATURDAY OT -->
            <div class="section">
                <h3>Saturday Overtime</h3>
            </div>

            <div class="section">
                <h4>Overtime</h4>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-3">
                    <input type="text" placeholder="Hours">
                    <input type="text" placeholder="Excess 8 Hours">
                    <input type="text" placeholder="PHP Equivalent">
                </div>
            </div>

            <div class="section">
                <h4>Night Differential 10PM - 6AM</h4>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-2">
                    <input type="text" placeholder="Day(s)">
                    <input type="text" placeholder="PHP Equivalent">
                </div>
            </div>

            <!-- BUTTON -->
            <div class="button-container">
                <button class="next-btn">Next</button>
            </div>

        </div>
    </div>

</div>

<style>
.page-wrapper{
    padding: 20px;
    height: 100%;
}

/* CARD */
.card{
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #9E9E9E;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.card-header{
    padding: 20px 25px;
    border-bottom: 1px solid #dcdcdc;
}

.card-header h2{
    font-size: 18px;
    color: #555;
    font-weight: 600;
}

.card-body{
    padding: 25px;
    overflow-y: auto;
}

/* SECTIONS */
.section{
    margin-bottom: 25px;
}

.section h3{
    font-size: 16px;
    color: #333;
    margin-bottom: 3px;
}

.section h4{
    font-size: 14px;
    color: #444;
    margin-bottom: 3px;
}

.subtitle{
    font-size: 12px;
    color: #888;
    margin-bottom: 10px;
}

/* GRIDS */
.grid-2{
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.grid-3{
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 15px;
}

.grid-4{
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
    gap: 15px;
}

.full-input{
    width: 100%;
}

/* INPUT */
input{
    height: 38px;
    border-radius: 6px;
    border: 1px solid #cfcfcf;
    background: #eeeeee;
    padding: 8px 10px;
    font-size: 13px;
    outline: none;
}

input:focus{
    background: #ffffff;
    border: 1px solid #7b5cff;
}

/* BUTTON */
.button-container{
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
}

.next-btn{
    background: linear-gradient(135deg, #7b5cff, #9b6bff);
    color: white;
    border: none;
    padding: 10px 28px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    box-shadow: 0 4px 10px rgba(123,92,255,0.3);
}
</style>