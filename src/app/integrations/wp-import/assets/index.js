/* global SiteFunctionalityImportRunner */

const RUNNER_CONTAINER_ID = 'site-functionality-import-runner-state';

const isRunnerPage = () =>
	document.getElementById(RUNNER_CONTAINER_ID) !== null;

const formatTimestamp = (timestamp) => {
	if (!timestamp) {
		return '—';
	}

	return new Date(timestamp * 1000).toLocaleString();
};

const setText = (id, text) => {
	const el = document.getElementById(id);
	if (el) {
		el.textContent = text;
	}
};

const fetchStatus = async () => {
	const params = new URLSearchParams({
		action: SiteFunctionalityImportRunner.action,
		nonce: SiteFunctionalityImportRunner.nonce,
	});

	const response = await fetch(SiteFunctionalityImportRunner.ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		},
		body: params.toString(),
	});

	if (!response.ok) {
		throw new Error('Request failed');
	}

	const json = await response.json();

	if (!json?.success || !json.data) {
		return null;
	}

	return json.data;
};

const updateStatusUI = (data) => {
	setText('site-functionality-import-runner-state', data.state || 'none');
	setText('site-functionality-import-runner-message', data.message || '');
	setText('site-functionality-import-runner-queued', formatTimestamp(data.queued_at));
	setText('site-functionality-import-runner-started', formatTimestamp(data.started_at));
	setText('site-functionality-import-runner-ended', formatTimestamp(data.ended_at));
};

const poll = async () => {
	try {
		const data = await fetchStatus();

		if (!data) {
			return;
		}

		updateStatusUI(data);

		if (data.state === 'success' || data.state === 'error') {
			return;
		}

		setTimeout(poll, 3000);
	} catch {
		setTimeout(poll, 8000);
	}
};

// ---- ENTRY POINT ----
if (
	typeof SiteFunctionalityImportRunner !== 'undefined' &&
	isRunnerPage()
) {
	poll();
}
