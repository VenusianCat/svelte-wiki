import {
	readable,
	writable
} from "svelte/store";

/* CONFIG */
let localhostAjaxURL = 'http://localhost/svelte-wiki/public/ajax.php';
let deployedAjaxURL = './ajax.php';
let ajaxURL = (window.location.hostname === "localhost") ? localhostAjaxURL : deployedAjaxURL;

export const config = readable({
	ajaxURL
});
