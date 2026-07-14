<?php

/**
 * Support Ticket Helper File
*/

/**
 * Get unseen ticket count
*/
if(!function_exists('countUnseenTicket'))
{
	function countUnseenTicket() {

		$ticketModel = model('App\Models\Ticket');

		return $ticketModel->where(['viewed' => 'no'])->countAllResults();
	}
}

/**
 * Get all unseen tickets
*/
if(!function_exists('getUnseenTickets'))
{
	function getUnseenTickets() {

		$ticketModel = model('App\Models\Ticket');

		$tickets = $ticketModel->where(['viewed' => 'no'])->findAll();

		return $tickets ?? null;
	}
}