describe('Repository list page', () => {
  it('successfully loads', () => {
    cy.visit('/');
    cy.get('.card-header').should('contain', 'git-bare-repo');
    cy.get('.card-body').should('contain', 'foobar');
  });
});
