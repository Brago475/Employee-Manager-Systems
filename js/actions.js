function fireEmployee(emp_no) {
    if (!confirm("Are you sure you want to fire this employee?")) return;

    fetch("/Employee-Manager-Systems/api/employee.php?action=fire", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "emp_no=" + encodeURIComponent(emp_no)
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            alert("Employee removed.");
            location.reload(); 
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(() => alert("Request failed."));
}
