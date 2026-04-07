import React, { useEffect, useRef } from 'react';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

export default function BillingChart({ data = [], labels = [], title = 'Histórico de Pagamentos' }) {
    const canvasRef = useRef(null);
    const chartRef  = useRef(null);

    useEffect(() => {
        if (!canvasRef.current) return;

        if (chartRef.current) chartRef.current.destroy();

        chartRef.current = new Chart(canvasRef.current, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Pagamentos (R$)',
                    data,
                    backgroundColor: 'rgba(26, 86, 219, 0.15)',
                    borderColor: '#1a56db',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: !!title, text: title, font: { size: 14, weight: '600' } },
                    tooltip: {
                        callbacks: {
                            label: ctx => `R$ ${ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`,
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => `R$ ${v.toLocaleString('pt-BR')}`,
                        },
                        grid: { color: 'rgba(0,0,0,0.04)' },
                    },
                    x: { grid: { display: false } },
                },
            },
        });

        return () => chartRef.current?.destroy();
    }, [data, labels]);

    return (
        <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <canvas ref={canvasRef} />
        </div>
    );
}
