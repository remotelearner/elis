/**
 * Clears out the export errors span element in between manual export
 * runs
 */
function rlip_clear_export_ui() {
    var element = document.getElementById("rlipexporterrors");

    if (element != null) {
    	//element is there, so clear it
        element.innerHTML = ""
    }
}